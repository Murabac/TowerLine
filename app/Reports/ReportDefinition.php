<?php

namespace App\Reports;

use App\Models\User;

class ReportDefinition
{
    /**
     * @param  list<string>  $filters
     */
    public function __construct(
        public readonly string $key,
        public readonly string $group,
        public readonly array $filters,
        public readonly bool $inspector = false,
        public readonly bool $operatorViewer = false,
        public readonly bool $requiresAudit = false,
        public readonly string $layout = 'table',
        public readonly ?string $dateColumn = null,
    ) {}

    public function title(): string
    {
        return __('app.reports.items.'.$this->langKey());
    }

    public function summary(): string
    {
        $key = 'app.reports.summaries.'.$this->langKey();

        return trans()->has($key) ? __($key) : '';
    }

    public function langKey(): string
    {
        return str_replace('.', '_', $this->key);
    }

    public function allowedFor(User $user): bool
    {
        if ($this->requiresAudit) {
            return $user->canTask('reports.audit');
        }

        if (! $user->canTask('reports.view')) {
            return false;
        }

        if ($user->isInspector()) {
            return $this->inspector;
        }

        if ($user->isOperatorViewer()) {
            return $this->operatorViewer;
        }

        return true;
    }
}
