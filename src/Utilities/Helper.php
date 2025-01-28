<?php

namespace Nahid\Apily\Utilities;

class Helper
{
    public static function arrayGet(array $data, string $key, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $data;
        }

        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }

            $data = $data[$segment];
        }

        return $data;
    }

    public static function replacePlaceholders(string $string, array $replacements): string
    {
        // Use a callback to replace placeholders
        return preg_replace_callback('/{{\s*(.*?)\s*}}/', function ($matches) use ($replacements) {
            // Extract the key from the placeholder (e.g., "person.name")
            $key = $matches[1];

            // Resolve the key in the replacements array
            return self::resolveKey($key, $replacements);
        }, $string);
    }

    protected static function resolveKey(string $key, array $array)
    {
        // Split the key into parts (e.g., "person.name" => ["person", "name"])
        $keys = explode('.', $key);

        // Traverse the array using the keys
        foreach ($keys as $k) {
            if (!is_array($array) || !array_key_exists($k, $array)) {
                // If the key doesn't exist, return the original placeholder
                return "{{$key}}";
            }

            // Move deeper into the array
            $array = $array[$k];
        }

        // Return the resolved value
        return $array;
    }
}