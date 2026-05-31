<?php

namespace App\Filament\NsConseil\Widgets;

use App\Services\AircallService;
use Filament\Widgets\Widget;

class AircallAppelsRecents extends Widget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = '60s';
    protected static string $view = 'filament.ns-conseil.widgets.aircall-appels-recents';

    public array $calls = [];
    public int $page = 1;
    public int $perPage = 25;
    public string $filterDirection = '';
    protected static bool $isLazy = true;

    public function mount(): void
    {
        $this->loadCalls();
    }

    public function loadCalls(): void
    {
        $filters = ['per_page' => $this->perPage, 'page' => $this->page, 'order' => 'desc'];
        if ($this->filterDirection) {
            $filters['direction'] = $this->filterDirection;
        }

        $this->calls = app(AircallService::class)->getCalls($filters);
    }

    public function setDirection(string $direction): void
    {
        $this->filterDirection = $direction;
        $this->page = 1;
        $this->loadCalls();
    }

    public function nextPage(): void
    {
        $this->page++;
        $this->loadCalls();
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadCalls();
        }
    }
}