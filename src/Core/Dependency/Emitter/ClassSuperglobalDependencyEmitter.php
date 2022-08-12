<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Dependency\Emitter;

use Qossmic\Deptrac\Core\Ast\AstMap\AstMap;
use Qossmic\Deptrac\Core\Ast\AstMap\DependencyTokenType;
use Qossmic\Deptrac\Core\Dependency\Dependency;
use Qossmic\Deptrac\Core\Dependency\DependencyList;

final class ClassSuperglobalDependencyEmitter implements DependencyEmitterInterface
{
    public function getName(): string
    {
        return 'ClassSuperglobalDependencyEmitter';
    }

    public function applyDependencies(AstMap $astMap, DependencyList $dependencyList): void
    {
        foreach ($astMap->getClassLikeReferences() as $classReference) {
            foreach ($classReference->dependencies as $dependency) {
                if (DependencyTokenType::SUPERGLOBAL_VARIABLE !== $dependency->type) {
                    continue;
                }
                $dependencyList->addDependency(
                    new Dependency(
                        $classReference->getToken(),
                        $dependency->token,
                        $dependency->fileOccurrence
                    )
                );
            }
        }
    }
}
