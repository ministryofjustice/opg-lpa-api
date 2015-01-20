<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @api
 */
class Time extends SymfonyConstraints\Time
{
    use ValidatorPathTrait;

    const INVALID_FORMAT_ERROR = 1;
    const INVALID_TIME_ERROR = 2;

    protected static $errorNames = array(
        self::INVALID_FORMAT_ERROR => 'INVALID_FORMAT_ERROR',
        self::INVALID_TIME_ERROR => 'INVALID_TIME_ERROR',
    );

    public $message = 'This value is not a valid time.';
}
