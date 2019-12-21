<?php declare(strict_types = 1);

namespace Nish\PHPStan\Test;

class ClassWithToString
{

	public function __toString()
	{
		return 'foo';
	}

	public function acceptsString(string $foo)
	{

	}

}
