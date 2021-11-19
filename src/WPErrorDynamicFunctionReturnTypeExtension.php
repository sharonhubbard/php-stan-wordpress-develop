<?php

/**
 * Set return type of various functions that support a `$wp_error` parameter.
 */

declare(strict_types=1);

namespace SzepeViktor\PHPStan\WordPress;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Type;

class WPErrorDynamicFunctionReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{
    /**
     * @var array<string, array{arg:int,true:string,false:string,maybe:string}>
     */
    private const SUPPORTED_FUNCTIONS = [
        'wp_insert_link' => [
            'arg' => 1,
            'true' => 'int',
            'false' => 'int|WP_Error',
            'maybe' => 'int|WP_Error',
        ],
        'wp_insert_category' => [
            'arg' => 1,
            'true' => 'int',
            'false' => 'int|WP_Error',
            'maybe' => 'int|WP_Error',
        ],
        'wp_set_comment_status' => [
            'arg' => 2,
            'true' => 'bool',
            'false' => 'true|WP_Error',
            'maybe' => 'bool|WP_Error',
        ],
        'wp_update_comment' => [
            'arg' => 1,
            'true' => '0|1|false',
            'false' => '0|1|WP_Error',
            'maybe' => '0|1|false|WP_Error',
        ],
    ];

    /** @var \PHPStan\PhpDoc\TypeStringResolver */
    protected $typeStringResolver;

    public function __construct(TypeStringResolver $typeStringResolver)
    {
        $this->typeStringResolver = $typeStringResolver;
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return array_key_exists($functionReflection->getName(), self::SUPPORTED_FUNCTIONS);
    }

    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
    {
        $name = $functionReflection->getName();
        $functionTypes = self::SUPPORTED_FUNCTIONS[$name] ?? null;

        if ($functionTypes === null) {
            throw new \PHPStan\ShouldNotHappenException(
                sprintf(
                    'Could not detect return types for function %s()',
                    $name
                )
            );
        }

        $wpErrorArgumentType = new ConstantBooleanType(false);
        $wpErrorArgument = $functionCall->args[$functionTypes['arg']] ?? null;

        if ($wpErrorArgument !== null) {
            $wpErrorArgumentType = $scope->getType($wpErrorArgument->value);
        }

        $type = $functionTypes['true'];

        if ($wpErrorArgumentType instanceof ConstantBooleanType) {
            if (true === $wpErrorArgumentType->getValue()) {
                $type = $functionTypes['false'];
            }
        } else {
            // When called with a $wp_error parameter that isn't a constant boolean, return default type
            $type = $functionTypes['maybe'];
        }

        return $this->typeStringResolver->resolve($type);
    }
}
