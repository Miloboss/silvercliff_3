<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateVersion;
use App\Mail\TemplatedMail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Email Templates';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 20;

    // ── FORM ─────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        $allowedPlaceholders = implode(', ', EmailTemplate::ALLOWED_PLACEHOLDERS);
        $allowedTags         = implode(', ', array_map(
            fn($t) => trim($t, '<>'),
            explode('><', trim(EmailTemplate::ALLOWED_HTML_TAGS, '<>'))
        ));

        return $form->schema([

            // ── Identity ─────────────────────────────────────────────────────
            Forms\Components\Section::make('Template Identity')
                ->schema([
                    Forms\Components\TextInput::make('key')
                        ->label('Template Key')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->disabled(fn ($record) => filled($record?->key))
                        ->helperText('e.g. booking_confirmation_guest — cannot change after creation.'),

                    Forms\Components\TextInput::make('name')
                        ->label('Display Name')
                        ->required(),

                    Forms\Components\Toggle::make('is_enabled')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Disabled → emails are skipped gracefully.'),

                    Forms\Components\Toggle::make('is_draft')
                        ->label('Save as Draft')
                        ->default(false),
                ])
                ->columns(2),

            // ── Subject & Header ─────────────────────────────────────────────
            Forms\Components\Section::make('Subject & Header')
                ->description('All fields support placeholders: ' . $allowedPlaceholders)
                ->schema([
                    Forms\Components\TextInput::make('subject_template')
                        ->label('Email Subject Line')
                        ->required()
                        ->rules([
                            fn () => function (string $attr, string $value, $fail) {
                                $bad = EmailTemplate::validatePlaceholders($value);
                                if ($bad) $fail('Unknown placeholders: ' . implode(', ', $bad));
                            },
                        ]),

                    Forms\Components\TextInput::make('header_title')
                        ->label('Header Title')
                        ->helperText('Large text at top of email (e.g. "Booking Received")'),

                    Forms\Components\TextInput::make('header_tagline')
                        ->label('Header Tagline')
                        ->helperText('Small subtitle below the header title.'),

                    Forms\Components\ColorPicker::make('accent_color')
                        ->label('Per-Template Accent Color')
                        ->helperText('Overrides the global email branding accent for this template only.'),
                ])
                ->columns(2),

            // ── Body ─────────────────────────────────────────────────────────
            Forms\Components\Section::make('Email Body')
                ->description("Rich text is supported. HTML tags sanitized to: {$allowedTags}. Placeholders: {$allowedPlaceholders}")
                ->schema([
                    Forms\Components\RichEditor::make('body_intro')
                        ->label('Body / Intro Content')
                        ->required()
                        ->toolbarButtons([
                            'bold', 'italic', 'underline',
                            'bulletList', 'orderedList',
                            'link',
                            'h2', 'h3',
                            'redo', 'undo',
                        ])
                        ->rules([
                            fn () => function (string $attr, string $value, $fail) {
                                $bad = EmailTemplate::validatePlaceholders(strip_tags($value));
                                if ($bad) $fail('Unknown placeholders in body: ' . implode(', ', $bad));
                            },
                        ]),

                    Forms\Components\RichEditor::make('policies_text')
                        ->label('Policies / Notes')
                        ->helperText('Shown as a highlighted note block. Plain text recommended.')
                        ->toolbarButtons(['bold', 'italic', 'link'])
                        ->nullable(),

                    Forms\Components\RichEditor::make('footer_text')
                        ->label('Footer Text')
                        ->toolbarButtons(['bold', 'italic'])
                        ->nullable(),
                ]),

            // ── Version info ─────────────────────────────────────────────────
            Forms\Components\Section::make('Version History')
                ->schema([
                    Forms\Components\Placeholder::make('version_info')
                        ->label('Current Version')
                        ->content(fn ($record) => $record
                            ? "v{$record->version} — saved " . $record->updated_at?->diffForHumans()
                            : 'Not yet saved'),

                    Forms\Components\Placeholder::make('version_count')
                        ->label('Saved Snapshots')
                        ->content(fn ($record) => $record
                            ? $record->versions()->count() . ' snapshots available (max 5)'
                            : '—'),
                ])
                ->columns(2)
                ->visibleOn('edit')
                ->collapsible(),
        ]);
    }

    // ── TABLE ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')->sortable()->searchable()->copyable()->fontFamily('mono'),
                Tables\Columns\TextColumn::make('name')->sortable(),
                Tables\Columns\IconColumn::make('is_enabled')->label('Active')->boolean(),
                Tables\Columns\IconColumn::make('is_draft')->label('Draft')->boolean(),
                Tables\Columns\TextColumn::make('version')->label('v#')->prefix('v')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Last Edited')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_enabled')->label('Active'),
                Tables\Filters\TernaryFilter::make('is_draft')->label('Draft'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // ── Preview ──────────────────────────────────────────────────
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (EmailTemplate $record): string =>
                        route('filament.admin.email-templates.preview', $record))
                    ->openUrlInNewTab(),

                // ── Preview PDF ───────────────────────────────────────────────
                Tables\Actions\Action::make('preview_pdf')
                    ->label('Preview PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->url(fn () => route('filament.admin.voucher.preview'))
                    ->openUrlInNewTab(),

                // ── Download Sample PDF ───────────────────────────────────────
                Tables\Actions\Action::make('download_pdf')
                    ->label('Sample PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn () => route('filament.admin.voucher.sample-download'))
                    ->openUrlInNewTab(),

                // ── Send Test ────────────────────────────────────────────────
                Tables\Actions\Action::make('send_test')
                    ->label('Send Test')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('test_email')
                            ->label('Send test to this address')
                            ->email()
                            ->required()
                            ->placeholder('you@example.com'),
                    ])
                    ->action(function (EmailTemplate $record, array $data): void {
                        try {
                            Mail::to($data['test_email'])->send(new TemplatedMail($record));
                            Notification::make()
                                ->title('Test email sent to ' . $data['test_email'])
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Send failed: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // ── Rollback ─────────────────────────────────────────────────
                Tables\Actions\Action::make('rollback')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->form(function (EmailTemplate $record): array {
                        $versions = $record->versions()->orderByDesc('version')->take(5)->get();
                        if ($versions->isEmpty()) {
                            return [
                                Forms\Components\Placeholder::make('no_versions')
                                    ->label('')
                                    ->content('No saved versions yet. Versions are created on each save.'),
                            ];
                        }
                        return [
                            Forms\Components\Select::make('version_id')
                                ->label('Select version to restore')
                                ->options($versions->mapWithKeys(fn ($ver) => [
                                    $ver->id => "v{$ver->version} — saved " . $ver->saved_at->diffForHumans(),
                                ]))
                                ->required(),
                        ];
                    })
                    ->action(function (EmailTemplate $record, array $data): void {
                        if (empty($data['version_id'])) return;
                        $ver = EmailTemplateVersion::find($data['version_id']);
                        if ($ver) {
                            $record->restoreFromVersion($ver);
                            Notification::make()
                                ->title('Restored to v' . $ver->version)
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── PAGES ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit'   => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
