<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Ast;

use Qossmic\Deptrac\Core\Ast\AstMap\AstMap;
use Qossmic\Deptrac\Core\InputCollector\InputCollectorInterface;
use Qossmic\Deptrac\Core\InputCollector\InputException;

class AstMapExtractor
{
    private ?AstMap $astMapCache = null;

    public function __construct(private readonly InputCollectorInterface $inputCollector, private readonly AstLoader $astLoader)
    {
    }

    /**
     * @throws InputException
     */
    public function extract(): AstMap
    {
        if (null === $this->astMapCache) {
            $this->astMapCache = $this->astLoader->createAstMap($this->inputCollector->collect());
        }

        return $this->astMapCache;
    }
}
