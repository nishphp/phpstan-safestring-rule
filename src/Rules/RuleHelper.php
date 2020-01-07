<?php

namespace Nish\PHPStan\Rules;

use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\IntegerType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\StringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;
use PHPStan\Type\NullType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\VerbosityLevel;
use Nish\PHPStan\Type\SafeStringType;

class RuleHelper
{
    public static function accepts(Type $type): bool
    {
        if ($type instanceof ErrorType)
            return true;
        if ($type instanceof IntegerType ||
            $type instanceof BooleanType ||
            $type instanceof NullType ||
            $type instanceof ConstantStringType)
            return true;

        if ($type instanceof SafeStringType)
            return true;

        if ($type instanceof ObjectType)
            return true;

        if ($type instanceof UnionType){
            $innerTypes = $type->getTypes();
            foreach ($innerTypes as $innerType){
                if ($innerType instanceof SafeStringType ||
                    $innerType instanceof ConstantStringType)
                    continue;

                if ($innerType instanceof StringType){
                    return false;
                }
            }
            return true;
        }

        if ($type->toString() instanceof StringType)
            return false;

        return true;
    }
}
