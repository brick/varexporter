<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes;

class SetState extends PublicAndPrivateProperties
{
    public static function __set_state(array $array) : self
    {
        $object = new self;

        // note: these checks would usually not be necessary in a typical app; they're just here because
        // we need to test our implementation against edge cases, such as unset properties.

        if (array_key_exists('foo', $array)) {
            $object->foo = $array['foo'];
        } else {
            unset($object->foo);
        }

        if (array_key_exists('bar', $array)) {
            $object->bar = $array['bar'];
        } else {
            unset($object->bar);
        }

        if (array_key_exists('baz', $array)) {
            $object->setBaz($array['baz']);
        } else {
            $object->unsetBaz();
        }

        // dynamic properties
        foreach ($array as $key => $value) {
            if ($key !== 'foo' && $key !== 'bar' && $key !== 'baz') {
                $object->{$key} = $value;
            }
        }

        return $object;
    }
}
