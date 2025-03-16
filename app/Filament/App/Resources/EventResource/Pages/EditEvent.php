<?php

namespace App\Filament\App\Resources\EventResource\Pages;

use App\Filament\App\Resources\EventResource;
use Filament\Actions;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\HasWizard;

class EditEvent extends EditRecord
{
    use HasWizard;

    protected static string $resource = EventResource::class;

    protected ?string $maxContentWidth = '2xl';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return parent::form($form)
            ->schema([
                Wizard::make(EventResource::getEventFormSteps())
                    ->startOnStep($this->getStartStep())
                    ->cancelAction($this->getCancelFormAction())
                    ->submitAction($this->getSubmitFormAction())
                    ->skippable($this->hasSkippableSteps())
                    ->contained(false),
            ])
            ->columns(null);
    }
}
