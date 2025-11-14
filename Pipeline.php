<?php

namespace Alphavel\Core;

class Pipeline
{
    private mixed $passable;

    private array $pipes = [];

    public function send(mixed $passable): self
    {
        $this->passable = $passable;

        return $this;
    }

    public function through(array $pipes): self
    {
        $this->pipes = $pipes;

        return $this;
    }

    public function then(callable $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $destination
        );

        return $pipeline($this->passable);
    }

    private function carry(): callable
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_string($pipe)) {
                    $pipe = app()->make($pipe);
                }

                return $pipe->handle($passable, $stack);
            };
        };
    }
}
