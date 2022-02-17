<?php declare(strict_types = 1);

namespace Nish\PHPStan\Type;

use PHPUnit\Framework\TestCase;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\StringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;

class DynamicReturnTypeExtensionTest extends TestCase
{
    public function testFunction()
    {
        $ext = new DynamicReturnTypeExtension('h', SafeHtmlStringType::class);

        $ref = $this->createMock(FunctionReflection::class);
        $ref->method('getName')->willReturn('h');
        $this->assertTrue($ext->isFunctionSupported($ref));

        $ref = $this->createMock(FunctionReflection::class);
        $ref->method('getName')->willReturn('raw');
        $this->assertFalse($ext->isFunctionSupported($ref));


        $ref = $this->createMock(MethodReflection::class);
        $ref->method('getName')->willReturn('h');
        $this->assertFalse($ext->isMethodSupported($ref));

    }

    public function testFunctionAccept()
    {
        $ext = new DynamicReturnTypeExtension('h', SafeHtmlStringType::class);

        $acceptor = $this->createMock(ParametersAcceptor::class);
        $acceptor->method('getReturnType')->willReturn(new StringType());
        $ref = $this->createMock(FunctionReflection::class);
        $ref->method('getVariants')->willReturn(
            [$acceptor]
        );
        $this->assertInstanceOf(
            SafeHtmlStringType::class,
            $ext->getTypeFromFunctionCall(
                $ref,
                $this->createMock(FuncCall::class),
                $this->createMock(Scope::class)));
    }

    public function testFunctionNotAccept()
    {
        $ext = new DynamicReturnTypeExtension('h', SafeHtmlStringType::class);

        $acceptor = $this->createMock(ParametersAcceptor::class);
        $acceptor->method('getReturnType')->willReturn(new IntegerType());
        $ref = $this->createMock(FunctionReflection::class);
        $ref->method('getVariants')->willReturn(
            [$acceptor]
        );
        $this->assertInstanceOf(
            IntegerType::class,
            $ext->getTypeFromFunctionCall(
                $ref,
                $this->createMock(FuncCall::class),
                $this->createMock(Scope::class)));

    }

    public function testFunctions()
    {
        $ext = new DynamicReturnTypeExtension(
            ['h', 'raw'], SafeHtmlStringType::class);

        $ref = $this->createMock(FunctionReflection::class);
        $ref->method('getName')->willReturn('h');
        $this->assertTrue($ext->isFunctionSupported($ref));

        $ref = $this->createMock(FunctionReflection::class);
        $ref->method('getName')->willReturn('raw');
        $this->assertTrue($ext->isFunctionSupported($ref));

        $ref = $this->createMock(FunctionReflection::class);
        $ref->method('getName')->willReturn('foo');
        $this->assertFalse($ext->isFunctionSupported($ref));
    }

    public function testMethod()
    {
        $ext = new DynamicReturnTypeExtension(
            'App\Html::checkbox', SafeHtmlStringType::class);

        $this->assertEquals('App\Html', $ext->getClass());


        $ref = $this->createMock(MethodReflection::class);
        $ref->method('getName')->willReturn('checkbox');
        $this->assertTrue($ext->isMethodSupported($ref));
        $this->assertTrue($ext->isStaticMethodSupported($ref));


        $ref = $this->createMock(FunctionReflection::class);
        $ref->method('getName')->willReturn('checkbox');
        $this->assertFalse($ext->isFunctionSupported($ref));
    }

    public function testMethodAccept()
    {
        $ext = new DynamicReturnTypeExtension(
            'App\Html::checkbox', SafeHtmlStringType::class);

        $acceptor = $this->createMock(ParametersAcceptor::class);
        $acceptor->method('getReturnType')->willReturn(new ConstantStringType('foo'));
        $ref = $this->createMock(MethodReflection::class);
        $ref->method('getVariants')->willReturn(
            [$acceptor]
        );
        $this->assertInstanceOf(
            SafeHtmlStringType::class,
            $ext->getTypeFromMethodCall(
                $ref,
                $this->createMock(MethodCall::class),
                $this->createMock(Scope::class)));
    }

    public function testMethodNotAccept()
    {
        $ext = new DynamicReturnTypeExtension(
            'App\Html::checkbox', SafeHtmlStringType::class);

        $acceptor = $this->createMock(ParametersAcceptor::class);
        $acceptor->method('getReturnType')->willReturn(new ObjectType('stdClass'));
        $ref = $this->createMock(MethodReflection::class);
        $ref->method('getVariants')->willReturn(
            [$acceptor]
        );
        $this->assertInstanceOf(
            ObjectType::class,
            $ext->getTypeFromMethodCall(
                $ref,
                $this->createMock(MethodCall::class),
                $this->createMock(Scope::class)));
    }


    public function testStaticMethod()
    {
        $ext = new DynamicReturnTypeExtension(
            'App\Html::checkbox', SafeHtmlStringType::class);

        $this->assertEquals('App\Html', $ext->getClass());


        $ref = $this->createMock(MethodReflection::class);
        $ref->method('getName')->willReturn('checkbox');
        $this->assertTrue($ext->isMethodSupported($ref));
        $this->assertTrue($ext->isStaticMethodSupported($ref));


        $ref = $this->createMock(FunctionReflection::class);
        $ref->method('getName')->willReturn('checkbox');
        $this->assertFalse($ext->isFunctionSupported($ref));


        $acceptor = $this->createMock(ParametersAcceptor::class);
        $acceptor->method('getReturnType')->willReturn(new StringType());
        $ref = $this->createMock(MethodReflection::class);
        $ref->method('getVariants')->willReturn(
            [$acceptor]
        );
        $this->assertInstanceOf(
            SafeHtmlStringType::class,
            $ext->getTypeFromStaticMethodCall(
                $ref,
                $this->createMock(StaticCall::class),
                $this->createMock(Scope::class)));

    }
}
