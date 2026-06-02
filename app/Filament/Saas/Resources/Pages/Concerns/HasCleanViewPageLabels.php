<?php

namespace App\Filament\Saas\Resources\Pages\Concerns;

trait HasCleanViewPageLabels
{
    public function getTitle(): string
    {
        return $this->getCleanRecordLabel();
    }

    public function getBreadcrumbs(): array
    {
        return [
            ...$this->getResourceBreadcrumbs(),
            $this->getCleanRecordLabel(),
        ];
    }

    protected function getCleanRecordLabel(): string
    {
        $recordTitle = method_exists($this, 'getRecordTitle') ? $this->getRecordTitle() : null;

        if (filled($recordTitle)) {
            return (string) $recordTitle;
        }

        return class_basename($this->getRecord()) . ' #' . $this->getRecord()->getKey();
    }
}
