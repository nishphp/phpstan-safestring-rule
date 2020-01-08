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
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
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

    public static function isSafeArray(ArrayType $type): bool
    {
        if ($type instanceof ConstantArrayType){
            foreach ($type->getValueTypes() as $innerType){
                if (!self::accepts($innerType))
                    return false;
            }
            return true;
        }

        return self::accepts($type->getItemType());
    }

    public static function isSafeUnionArray(UnionType $type): bool
    {
        foreach ($type->getTypes() as $innerType){
            if ($innerType instanceof UnionType){
                if (!self::isSafeUnionArray($innerType))
                    return false;
            }elseif ($innerType instanceof ArrayType){
                if (!self::isSafeArray($innerType))
                    return false;
            }else{
                return false;
            }
        }
        return true;
    }
}
