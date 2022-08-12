<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Supportive\OutputFormatter;

use Qossmic\Deptrac\Contract\OutputFormatter\OutputFormatterInput;
use Qossmic\Deptrac\Contract\OutputFormatter\OutputFormatterInterface;
use Qossmic\Deptrac\Contract\OutputFormatter\OutputInterface;
use Qossmic\Deptrac\Contract\Result\LegacyResult;
use Qossmic\Deptrac\Contract\Result\SkippedViolation;
use Qossmic\Deptrac\Contract\Result\Violation;
use Qossmic\Deptrac\Core\Ast\AstMap\FileOccurrence;
use Qossmic\Deptrac\Core\Dependency\InheritDependency;
use function count;

/**
 * @internal
 */
final class ConsoleOutputFormatter implements OutputFormatterInterface
{
    public static function getName(): string
    {
        return 'console';
    }

    public function finish(
        LegacyResult $result,
        OutputInterface $output,
        OutputFormatterInput $outputFormatterInput
    ): void {
        foreach ($result->rules as $rule) {
            if (!$rule instanceof Violation && !$rule instanceof SkippedViolation) {
                continue;
            }

            if (!$outputFormatterInput->reportSkipped && $rule instanceof SkippedViolation) {
                continue;
            }

            $this->printViolation($rule, $output);
        }

        if ($outputFormatterInput->reportUncovered) {
            $this->printUncovered($result, $output);
        }

        if ($result->hasErrors()) {
            $this->printErrors($result, $output);
        }

        if ($result->hasWarnings()) {
            $this->printWarnings($result, $output);
        }

        $this->printSummary($result, $output);
    }

    private function printViolation(Violation|SkippedViolation $rule, OutputInterface $output): void
    {
        $dependency = $rule->getDependency();

        $output->writeLineFormatted(
            sprintf(
                '%s<info>%s</info> must not depend on <info>%s</info> (%s on %s)',
                $rule instanceof SkippedViolation ? '[SKIPPED] ' : '',
                $dependency->getDepender()->toString(),
                $dependency->getDependent()->toString(),
                $rule->getDependerLayer(),
                $rule->getDependentLayer()
            )
        );
        $this->printFileOccurrence($output, $dependency->getFileOccurrence());

        if ($dependency instanceof InheritDependency) {
            $this->printInheritPath($output, $dependency);
        }
    }

    private function printInheritPath(OutputInterface $output, InheritDependency $dependency): void
    {
        $buffer = [];
        $astInherit = $dependency->inheritPath;
        foreach ($astInherit->getPath() as $p) {
            array_unshift($buffer, sprintf("\t%s::%d", $p->classLikeName->toString(), $p->fileOccurrence->line));
        }

        $buffer[] = sprintf("\t%s::%d", $astInherit->classLikeName->toString(), $astInherit->fileOccurrence->line);
        $buffer[] = sprintf(
            "\t%s::%d",
            $dependency->originalDependency->getDependent()->toString(),
            $dependency->originalDependency->getFileOccurrence()->line
        );

        $output->writeLineFormatted(implode(" -> \n", $buffer));
    }

    private function printSummary(LegacyResult $result, OutputInterface $output): void
    {
        $violationCount = count($result->violations());
        $skippedViolationCount = count($result->skippedViolations());
        $uncoveredCount = count($result->uncovered());
        $allowedCount = count($result->allowed());
        $warningsCount = count($result->warnings);
        $errorsCount = count($result->errors);

        $output->writeLineFormatted('');
        $output->writeLineFormatted('Report:');
        $output->writeLineFormatted(
            sprintf(
                '<fg=%s>Violations: %d</>',
                $violationCount > 0 ? 'red' : 'default',
                $violationCount
            )
        );
        $output->writeLineFormatted(
            sprintf(
                '<fg=%s>Skipped violations: %d</>',
                $skippedViolationCount > 0 ? 'yellow' : 'default',
                $skippedViolationCount
            )
        );
        $output->writeLineFormatted(
            sprintf(
                '<fg=%s>Uncovered: %d</>',
                $uncoveredCount > 0 ? 'yellow' : 'default',
                $uncoveredCount
            )
        );
        $output->writeLineFormatted(sprintf('<info>Allowed: %d</info>', $allowedCount));
        $output->writeLineFormatted(
            sprintf(
                '<fg=%s>Warnings: %d</>',
                $warningsCount > 0 ? 'yellow' : 'default',
                $warningsCount
            )
        );
        $output->writeLineFormatted(
            sprintf(
                '<fg=%s>Errors: %d</>',
                $errorsCount > 0 ? 'red' : 'default',
                $errorsCount
            )
        );
    }

    private function printUncovered(LegacyResult $result, OutputInterface $output): void
    {
        $uncovered = $result->uncovered();
        if ([] === $uncovered) {
            return;
        }

        $output->writeLineFormatted('<comment>Uncovered dependencies:</comment>');
        foreach ($uncovered as $u) {
            $dependency = $u->getDependency();
            $output->writeLineFormatted(
                sprintf(
                    '<info>%s</info> has uncovered dependency on <info>%s</info> (%s)',
                    $dependency->getDepender()->toString(),
                    $dependency->getDependent()->toString(),
                    $u->layer
                )
            );
            $this->printFileOccurrence($output, $dependency->getFileOccurrence());

            if ($dependency instanceof InheritDependency) {
                $this->printInheritPath($output, $dependency);
            }
        }
    }

    private function printFileOccurrence(OutputInterface $output, FileOccurrence $fileOccurrence): void
    {
        $output->writeLineFormatted($fileOccurrence->filepath.'::'.$fileOccurrence->line);
    }

    private function printErrors(LegacyResult $result, OutputInterface $output): void
    {
        $output->writeLineFormatted('');
        foreach ($result->errors as $error) {
            $output->writeLineFormatted(sprintf('<fg=red>[ERROR]</> %s', (string) $error));
        }
    }

    private function printWarnings(LegacyResult $result, OutputInterface $output): void
    {
        $output->writeLineFormatted('');
        foreach ($result->warnings as $warning) {
            $output->writeLineFormatted(sprintf('<fg=yellow>[WARNING]</> %s', (string) $warning));
        }
    }
}
