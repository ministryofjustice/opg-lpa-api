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
 * @author Miha Vrhovnik <miha.vrhovnik@pagein.si>
 *
 * @api
 */
class Currency extends SymfonyConstraints\Currency
{
    use ValidatorPathTrait;

    public $message = 'This value is not a valid currency.';
}