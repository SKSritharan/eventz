<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\EventResource\Pages;
use App\Filament\App\Resources\EventResource\RelationManagers;
use App\Models\Category;
use App\Models\Event;
use App\Models\SubCategory;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function getEventFormSteps(): array
    {
        return [
            Step::make('Basic Info')
                ->schema([
                    Section::make('Event Details')
                        ->description('Enter essential details to kickstart your event planning journey.')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('title')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('Enter event title')
                                        ->columnSpanFull()
                                        ->extraAttributes(['class' => 'font-semibold']),

                                    Select::make('organizer_id')
                                        ->relationship('organizer', 'name', fn(Builder $query) => $query
                                            ->where('user_id', auth()->id())
                                            ->where('is_active', true))
                                        ->createOptionModalHeading('Create Organizer')
                                        ->createOptionForm([
                                            Hidden::make('user_id')
                                                ->default(auth()->id()),
                                            TextInput::make('name')
                                                ->label('Organizer Name')
                                                ->required()
                                                ->maxLength(255),
                                        ])
                                        ->required()
                                        ->preload()
                                        ->searchable()
                                        ->placeholder('Select an organizer'),

                                    Select::make('category_id')
                                        ->label('Category')
                                        ->relationship('category', 'name')
                                        ->required()
                                        ->live()
                                        ->preload()
                                        ->afterStateUpdated(fn(Set $set) => $set('sub_category_id', null))
                                        ->placeholder('Select a category')
                                        ->searchable(),

                                    Select::make('sub_category_id')
                                        ->label('Sub Category')
                                        ->options(fn(Get $get) => SubCategory::where('category_id', $get('category_id'))
                                            ->pluck('name', 'id'))
                                        ->disabled(fn(Get $get) => !$get('category_id'))
                                        ->placeholder(fn(Get $get
                                        ) => $get('category_id') ? 'Select a sub category' : 'Select category first')
                                        ->nullable()
                                        ->searchable(),

                                    Toggle::make('is_online')
                                        ->label('Online Event')
                                        ->live()
                                        ->default(false)
                                        ->required()
                                        ->inline(false)
                                        ->helperText('Toggle for online or in-person event')
                                        ->columnSpanFull(),

                                    TextInput::make('online_link')
                                        ->label('Online Event Link')
                                        ->visible(fn(Get $get) => $get('is_online'))
                                        ->required(fn(Get $get) => $get('is_online'))
                                        ->url()
                                        ->placeholder('https://example.com')
                                        ->columnSpanFull(),

                                    Textarea::make('note')
                                        ->label('Online Event Notes')
                                        ->visible(fn(Get $get) => $get('is_online'))
                                        ->maxLength(65535)
                                        ->placeholder('Add instructions for attendees')
                                        ->rows(3)
                                        ->columnSpanFull(),

                                    Select::make('location')
                                        ->visible(fn(Get $get) => !$get('is_online'))
                                        ->required(fn(Get $get) => !$get('is_online'))
                                        ->searchable()
                                        ->preload(false)
                                        ->placeholder('Type to search locations...')
                                        ->getSearchResultsUsing(function (string $search): array {
                                            $apiKey = config('services.google_maps.key');
                                            $url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';
                                            $response = Http::get($url, [
                                                'input' => $search,
                                                'key' => $apiKey,
                                                'types' => 'geocode',
                                                'language' => 'en',
                                            ]);
                                            if ($response->successful()) {
                                                $predictions = $response->json()['predictions'] ?? [];
                                                return collect($predictions)
                                                    ->mapWithKeys(fn($prediction
                                                    ) => [$prediction['place_id'] => $prediction['description']])
                                                    ->all();
                                            }
                                            return [];
                                        })
                                        ->getOptionLabelUsing(fn($value): ?string => $value)
                                        ->afterStateUpdated(function (Set $set, $state) {
                                            if ($state) {
                                                $apiKey = config('services.google_maps.key');
                                                $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json',
                                                    [
                                                        'place_id' => $state,
                                                        'key' => $apiKey,
                                                        'fields' => 'geometry',
                                                    ]);
                                                if ($response->successful()) {
                                                    $location = $response->json()['result']['geometry']['location'] ?? null;
                                                    if ($location) {
                                                        $set('latitude', $location['lat']);
                                                        $set('longitude', $location['lng']);
                                                    }
                                                }
                                            }
                                        })
                                        ->columnSpanFull(),
                                    Hidden::make('latitude'),
                                    Hidden::make('longitude'),
                                ]),
                        ])
                        ->collapsible()
                        ->extraAttributes(['class' => 'bg-white shadow-sm rounded-lg p-6']),

                    Section::make('Event Timing')
                        ->description('Specify when your event starts and ends.')
                        ->schema([
                            DateTimePicker::make('start_date')
                                ->label('Start Date & Time')
                                ->required()
                                ->live()
                                ->seconds(false)
                                ->minDate(now()->startOfDay())
                                ->beforeOrEqual('end_date')
                                ->placeholder('Select start date and time')
                                ->default(now()->startOfDay())
                                ->afterStateUpdated(fn(Set $set) => $set('end_date', null)),
                            DateTimePicker::make('end_date')
                                ->label('End Date & Time')
                                ->required()
                                ->live()
                                ->seconds(false)
                                ->minDate(fn(Get $get) => $get('start_date'))
                                ->afterOrEqual('start_date')
                                ->disabled(fn(Get $get) => !$get('start_date'))
                                ->placeholder('Select end date and time'),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->extraAttributes(['class' => 'bg-white shadow-sm rounded-lg p-6']),

                    Section::make('Event Content')
                        ->description('Captivate your audience with a compelling description and image.')
                        ->schema([
                            FileUpload::make('image')
                                ->required()
                                ->image()
                                ->maxSize(10240)
                                ->imageEditor()
                                ->directory('event-images')
                                ->columnSpanFull(),
                            RichEditor::make('description')
                                ->required()
                                ->toolbarButtons([
                                    'bold', 'italic', 'underline', 'strike',
                                    'link', 'orderedList', 'bulletList',
                                ])
                                ->placeholder('Describe your event')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->extraAttributes(['class' => 'bg-white shadow-sm rounded-lg p-6']),
                ])
                ->icon('heroicon-o-information-circle'),

            Step::make('Ticket')
                ->schema([
                    Section::make('Event Tickets')
                        ->description('Add ticket types for your event. Specify pricing, availability, and timing.')
                        ->schema([
                            Repeater::make('tickets')
                                ->hiddenLabel()
                                ->relationship('tickets')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Ticket Name')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull()
                                        ->placeholder('e.g., General Admission')
                                        ->extraAttributes(['class' => 'font-semibold']),

                                    Grid::make(2)
                                        ->schema([
                                            Toggle::make('is_free')
                                                ->label('Free Ticket')
                                                ->live()
                                                ->default(false)
                                                ->inline(false)
                                                ->helperText('Enable for free entry'),

                                            TextInput::make('price')
                                                ->label('Price')
                                                ->disabled(fn(Get $get) => $get('is_free'))
                                                ->required(fn(Get $get) => !$get('is_free'))
                                                ->numeric()
                                                ->default(0.00)
                                                ->minValue(0)
                                                ->prefix('$')
                                                ->placeholder('0.00')
                                                ->extraAttributes(['class' => 'disabled:opacity-50']),

                                            TextInput::make('quantity')
                                                ->label('Available Quantity')
                                                ->required()
                                                ->integer()
                                                ->minValue(1)
                                                ->placeholder('e.g., 100'),

                                            Toggle::make('is_visible')
                                                ->label('Visible to Public')
                                                ->default(true)
                                                ->inline(false)
                                                ->helperText('Hide from public view if disabled'),

                                            DateTimePicker::make('start_date')
                                                ->label('Ticket Sale Start')
                                                ->required()
                                                ->seconds(false)
                                                ->minDate(fn(Get $get) => $get('../../start_date'))
                                                ->maxDate(fn(Get $get) => $get('../../end_date'))
                                                ->default(fn(Get $get) => $get('../../start_date'))
                                                ->placeholder('Select start date and time')
                                                ->columnSpanFull(),

                                            DateTimePicker::make('end_date')
                                                ->label('Ticket Sale End')
                                                ->required()
                                                ->seconds(false)
                                                ->minDate(fn(Get $get) => $get('start_date'))
                                                ->maxDate(fn(Get $get) => $get('../../end_date'))
                                                ->default(fn(Get $get) => $get('../../end_date'))
                                                ->placeholder('Select end date and time')
                                                ->columnSpanFull(),
                                        ]),

                                    Textarea::make('description')
                                        ->label('Ticket Description')
                                        ->maxLength(65535)
                                        ->columnSpanFull()
                                        ->placeholder('Add ticket details or restrictions')
                                        ->rows(3),
                                ])
                                ->grid(1)
                                ->minItems(1)
                                ->addActionLabel('Add Another Ticket')
                                ->deleteAction(fn($action) => $action
                                    ->requiresConfirmation()
                                    ->color('danger'))
                                ->itemLabel(fn(array $state): string => $state['name'] ?? 'New Ticket')
                                ->collapsible()
                                ->cloneable()
                        ])
                        ->extraAttributes(['class' => 'shadow-sm rounded-lg p-6']),
                ])->icon('heroicon-o-ticket'),

            Step::make('Publish')
                ->schema([
                    Section::make('Event Preview')
                        ->description('Review your event details and tickets before publishing.')
                        ->schema([
//                            View::make('event-preview')
//                            ->view()
                        ])
                        ->collapsible()
                        ->extraAttributes(['class' => 'bg-gray-50 p-4 rounded-lg']),
                ])->icon('heroicon-o-globe-alt'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->extremePaginationLinks()
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('image')
                        ->size(120)
                        ->defaultImageUrl('https://flowbite.com/docs/images/examples/image-1@2x.jpg')
                        ->extraImgAttributes(['class' => 'shadow-md']),
                    Tables\Columns\TextColumn::make('title')
                        ->searchable()
                        ->weight(FontWeight::Bold)
                        ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                        ->wrap()
                        ->color('gray-900'),

                    Tables\Columns\TextColumn::make('start_date')
                        ->date('D, M j, Y \a\t h:i A')
                        ->color('gray-600')
                        ->size(Tables\Columns\TextColumn\TextColumnSize::Small),

                    Tables\Columns\TextColumn::make('description')
                        ->formatStateUsing(fn($state) => new HtmlString(
                            \Illuminate\Support\Str::limit(strip_tags($state), 50)
                        ))
                        ->color('gray-500')
                        ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                        ->html(),

                    Tables\Columns\TextColumn::make('organizer.name')
                        ->label('Organizer')
                        ->searchable()
                        ->color('gray-600')
                        ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                        ->prefix('By: '),
                ])->space(2),
                Tables\Columns\Layout\Panel::make([
                    Split::make([
                        Tables\Columns\TextColumn::make('min_ticket_price')
                            ->label('Starting at')
                            ->getStateUsing(function ($record) {
                                $minPrice = $record->tickets->min('price');
                                return $minPrice !== null ? '$'.number_format($minPrice, 2) : 'N/A';
                            })
                            ->color('gray-900')
                            ->weight(FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Medium)
                            ->alignLeft(),

                        Tables\Columns\TextColumn::make('buy_now')
                            ->label('Buy Now')
                            ->badge()
                            ->size(TextColumnSize::Large)
                            ->getStateUsing(function ($record) {
                                $minPrice = $record->tickets->min('price');
                                return $minPrice !== null ? 'Buy Now' : '';
                            })
                            ->url('/')
                    ])
                ])
            ])
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('mine')
                    ->nullable()
                    ->trueLabel('Mine')
                    ->falseLabel('All')
                    ->placeholder('All Events')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('organizer',
                            fn(Builder $subQuery) => $subQuery->whereIn('id', auth()->user()->organizers->pluck('id'))),
                        false: fn(Builder $query) => $query,
                    )
                    ->attribute('mine'),

                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category')
                    ->placeholder('All Categories')
                    ->preload()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('start_date')
                    ->label('Start Date')
                    ->placeholder('Any Date')
                    ->options([
                        'this_week' => 'This Week',
                        'this_month' => 'This Month',
                        'next_week' => 'Next Week',
                        'next_month' => 'Next Month',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        match ($value) {
                            'this_week' => $query->whereBetween('start_date', [
                                Carbon::now()->startOfWeek(),
                                Carbon::now()->endOfWeek(),
                            ]),
                            'this_month' => $query->whereBetween('start_date', [
                                Carbon::now()->startOfMonth(),
                                Carbon::now()->endOfMonth(),
                            ]),
                            'next_week' => $query->whereBetween('start_date', [
                                Carbon::now()->addWeek()->startOfWeek(),
                                Carbon::now()->addWeek()->endOfWeek(),
                            ]),
                            'next_month' => $query->whereBetween('start_date', [
                                Carbon::now()->addMonth()->startOfMonth(),
                                Carbon::now()->addMonth()->endOfMonth(),
                            ]),
                            default => null,
                        };
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersTriggerAction(fn($action) => $action
                ->button()
                ->label('Filter Events')
                ->color('gray'))
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->size(ActionSize::Large)
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->label('Edit')
                    ->tooltip('Edit this event'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->size(ActionSize::Large)
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->label('Delete')
                    ->tooltip('Delete this event')
                    ->requiresConfirmation(),
            ])
            ->actionsAlignment('end')
            ->bulkActions([
                //
            ])
            ->defaultSort('start_date')
            ->paginationPageOptions([10, 25, 50])
            ->emptyStateHeading('No Events Found')
            ->emptyStateDescription('Create an event to get started.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Event')
                    ->color('primary'),
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'organizer.name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->title;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $startDate = date('M d, Y', strtotime($record->start_date));
        $startTime = date('H:i A', strtotime($record->start_time));

        return [
            'Organizer' => $record->organizer->name,
            'Start Date' => $startDate.' at '.$startTime,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'view' => Pages\ViewEvent::route('/{record}'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
