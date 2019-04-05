<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes;

class SetState extends PublicAndPrivateProperties
{
    public static function __set_state(array $array) : self
    {
        $object = new self;

        $object->foo = $array['foo'];
        $object->bar = $array['bar'];
        $object->setBaz($array['baz']);

        // dynamic properties
        foreach ($array as $key => $value) {
            if ($key !== 'foo' && $key !== 'bar' && $key !== 'baz') {
                $object->{$key} = $value;
            }
        }

        return $object;
    }
}
