<?php

namespace Nahid\Apily\Assertions;

class TestRunner
{
    /**
     * @var array<string, bool>
     */
    private array $assertions = [];
    public function __construct(protected BaseAssertion $assertion)
    {
    }

    public function getAssertions(): array
    {
        return $this->assertions;
    }

    public function run(): void
    {
        $reflection = new \ReflectionClass($this->assertion);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if(str_starts_with($method->name, 'test')) {
                $title = $method->name;
                $attributes = $method->getAttributes();

                if ($attributes) {
                    $title = $attributes[0]->newInstance()->getTitle();
                }
                try {
                    $method->invoke($this->assertion);
                    $this->assertions[$title] = true;
                } catch (\Exception $e) {
                    $this->assertions[$title] = $e->getMessage();
                }
            }
        }
        
    }
}