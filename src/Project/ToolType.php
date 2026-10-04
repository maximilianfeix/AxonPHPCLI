<?php

declare(strict_types=1);

namespace AxonPHP\Cli\Project;

enum ToolType: string
{
    case Tests = 'tests';
    case StaticAnalysis = 'static-analysis';
    case CodeStyle = 'code-style';
    case Dependencies = 'dependencies';

    public function label(): string
    {
        return match ($this) {
            self::Tests => 'Tests',
            self::StaticAnalysis => 'Static analysis',
            self::CodeStyle => 'Code style',
            self::Dependencies => 'Dependencies',
        };
    }
}
