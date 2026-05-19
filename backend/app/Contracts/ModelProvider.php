<?php

namespace App\Contracts;

interface ModelProvider
{
    public function name(): string;

    public function supportsModel(string $model): bool;

    /**
     * @param array{system?:string,prompt:string,model:string,temperature?:float,max_tokens?:int,tools?:array} $payload
     * @return array{response:string,model:string,usage?:array,raw?:array,mock?:bool}
     */
    public function complete(array $payload): array;
}
