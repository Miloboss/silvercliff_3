<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Support\PublicStorageUrl;
use Filament\Forms;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EmailBrandingSettings extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-paint-brush';
    protected static ?string $navigationLabel = 'Email Branding';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int    $navigationSort  = 19;
    protected static string  $view            = 'filament.pages.email-branding-settings';

    // Form state
    public array $data = [];

    // ── Keys we manage ───────────────────────────────────────────────────────
    protected static array $keys = [
        'email_primary_color', 'email_secondary_color', 'email_button_bg', 'email_button_text',
        'email_logo_size', 'email_card_padding', 'email_border_radius', 'email_heading_scale',
        'email_header_style', 'email_logo', 'email_header_bg_image',
        
        'header_background_color', 'header_text_color', 'body_background_color', 'card_background_color',
        'primary_button_color', 'primary_button_text_color', 'accent_border_color', 'divider_color',
        'footer_background_color',
        
        'header_padding', 'body_padding', 'card_radius', 'button_radius',
        
        'title_font_size', 'body_font_size', 'line_height',
        
        'show_logo', 'logo_max_width', 'show_dividers',
    ];

    #[On('file-upload-finished')]
    public function onFileUploadFinished($fieldName = null): void
    {
        Log::info('File upload finished', ['field' => $fieldName]);
    }

    public function updated($name, $value): void
    {
        if (in_array($name, ['data.email_logo', 'data.email_header_bg_image'])) {
            Log::debug("FileUpload field updated", [
                'field' => $name,
                'value' => $value,
                'value_type' => gettype($value),
            ]);
        }
    }

    public function mount(): void
    {
        $settings = SiteSetting::whereIn('key', self::$keys)->pluck('value', 'key');
        PublicStorageUrl::fromPath($settings->get('email_logo'));
        PublicStorageUrl::fromPath($settings->get('email_header_bg_image'));

        // Map file paths to proper URLs for FileUpload
        $this->form->fill([
            'email_primary_color'   => $settings->get('email_primary_color',   '#1e3a1a'),
            'email_secondary_color' => $settings->get('email_secondary_color', '#3a6b2a'),
            'email_button_bg'       => $settings->get('email_button_bg',       '#c53030'),
            'email_button_text'     => $settings->get('email_button_text',     '#ffffff'),
            'email_logo_size'       => $settings->get('email_logo_size',       'md'),
            'email_card_padding'    => $settings->get('email_card_padding',    'md'),
            'email_border_radius'   => $settings->get('email_border_radius',   '8'),
            'email_heading_scale'   => $settings->get('email_heading_scale',   'md'),
            'email_header_style'    => $settings->get('email_header_style',    'solid'),
            'email_logo'            => $settings->get('email_logo',            '') ?: null,
            'email_header_bg_image' => $settings->get('email_header_bg_image', '') ?: null,

            'header_background_color'   => $settings->get('header_background_color'),
            'header_text_color'         => $settings->get('header_text_color'),
            'body_background_color'     => $settings->get('body_background_color'),
            'card_background_color'     => $settings->get('card_background_color'),
            'primary_button_color'      => $settings->get('primary_button_color'),
            'primary_button_text_color' => $settings->get('primary_button_text_color'),
            'accent_border_color'       => $settings->get('accent_border_color'),
            'divider_color'             => $settings->get('divider_color'),
            'footer_background_color'   => $settings->get('footer_background_color'),

            'header_padding' => $settings->get('header_padding'),
            'body_padding'   => $settings->get('body_padding'),
            'card_radius'    => $settings->get('card_radius'),
            'button_radius'  => $settings->get('button_radius'),

            'title_font_size' => $settings->get('title_font_size'),
            'body_font_size'  => $settings->get('body_font_size'),
            'line_height'     => $settings->get('line_height'),

            'show_logo'       => $settings->has('show_logo') ? (bool)$settings->get('show_logo') : true,
            'logo_max_width'  => $settings->get('logo_max_width'),
            'show_dividers'   => $settings->has('show_dividers') ? (bool)$settings->get('show_dividers') : true,
        ]);
    }

    public function form(Form $form): Form
    {
        $resolveUploadedFile = static function (
            BaseFileUpload $component,
            string $file,
            string | array | null $storedFileNames
        ): ?array {
            $storage = $component->getDisk();

            if (! $storage->exists($file)) {
                return null;
            }

            $size = 0;
            $type = null;

            try {
                $size = $storage->size($file);
                $type = $storage->mimeType($file);
            } catch (\Throwable $exception) {
                Log::warning('Unable to read file metadata for branding upload.', [
                    'file' => $file,
                    'message' => $exception->getMessage(),
                ]);
            }

            return [
                'name' => ($component->isMultiple() ? ($storedFileNames[$file] ?? null) : $storedFileNames) ?? basename($file),
                'size' => $size,
                'type' => $type,
                'url' => PublicStorageUrl::fromPath($file),
            ];
        };

        $safeUploadedFileName = static function (TemporaryUploadedFile $file): string {
            $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $baseName = filled($baseName) ? $baseName : 'branding-image';

            return "{$baseName}-" . Str::ulid() . '.' . strtolower($file->getClientOriginalExtension());
        };

        return $form
            ->schema([
                Forms\Components\Section::make('Branding')
                    ->description('Manage your email brand identity, colors, and logo.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('email_logo')
                            ->label('Email Logo')
                            ->disk('public')
                            ->directory('branding')
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->fetchFileInformation(false)
                            ->maxSize(5120)
                            ->getUploadedFileUsing($resolveUploadedFile)
                            ->getUploadedFileNameForStorageUsing($safeUploadedFileName)
                            ->helperText('Upload PNG, JPEG, or WebP. Leave blank to use site logo.')
                            ->columnSpanFull(),

                        Forms\Components\ColorPicker::make('header_background_color')->label('Header Background Color (Hex)'),
                        Forms\Components\ColorPicker::make('header_text_color')->label('Header Text Color (Hex)'),
                        Forms\Components\ColorPicker::make('body_background_color')->label('Body Background Color (Hex)'),
                        Forms\Components\ColorPicker::make('card_background_color')->label('Card Background Color (Hex)'),
                        Forms\Components\ColorPicker::make('primary_button_color')->label('Primary Button Color (Hex)'),
                        Forms\Components\ColorPicker::make('primary_button_text_color')->label('Primary Button Text Color (Hex)'),
                        Forms\Components\ColorPicker::make('accent_border_color')->label('Accent Border Color (Hex)'),
                        Forms\Components\ColorPicker::make('divider_color')->label('Divider Color (Hex)'),
                        Forms\Components\ColorPicker::make('footer_background_color')->label('Footer Background Color (Hex)'),
                        
                        // Legacy fields - kept so data isn't lost on save
                        Forms\Components\Hidden::make('email_primary_color'),
                        Forms\Components\Hidden::make('email_secondary_color'),
                        Forms\Components\Hidden::make('email_button_bg'),
                        Forms\Components\Hidden::make('email_button_text'),
                        Forms\Components\Hidden::make('email_header_style'),
                        Forms\Components\Hidden::make('email_header_bg_image'),
                    ]),

                Forms\Components\Section::make('Layout')
                    ->description('Adjust spacing, padding, and corner radius.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('header_padding')->label('Header Padding (px)')->numeric(),
                        Forms\Components\TextInput::make('body_padding')->label('Body Padding (px)')->numeric(),
                        Forms\Components\TextInput::make('card_radius')->label('Card Radius (px)')->numeric(),
                        Forms\Components\TextInput::make('button_radius')->label('Button Radius (px)')->numeric(),
                        
                        // Legacy Layout fields
                        Forms\Components\Hidden::make('email_card_padding'),
                        Forms\Components\Hidden::make('email_border_radius'),
                        Forms\Components\Hidden::make('email_logo_size'),
                        Forms\Components\Hidden::make('email_heading_scale'),
                    ]),

                Forms\Components\Section::make('Typography')
                    ->description('Set precise font sizes and line heights.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title_font_size')->label('Title Font Size (px)')->numeric(),
                        Forms\Components\TextInput::make('body_font_size')->label('Body Font Size (px)')->numeric(),
                        Forms\Components\TextInput::make('line_height')->label('Line Height (decimal)')->numeric()->step(0.1),
                    ]),

                Forms\Components\Section::make('Toggles')
                    ->description('Enable or disable layout elements.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('show_logo')->label('Show Logo')->default(true),
                        Forms\Components\Toggle::make('show_dividers')->label('Show Dividers')->default(true),
                        Forms\Components\TextInput::make('logo_max_width')->label('Logo Max Width (px)')->numeric(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $state = $this->form->getState();
            
            \Log::info('EmailBrandingSettings save() called', [
                'state_keys' => array_keys($state),
            ]);

            foreach ($state as $key => $value) {
                if ($key === 'current_logo_path' || $value === null || ($value === '' && !is_bool($value))) {
                    continue;
                }

                $filename = is_array($value) ? Arr::first($value) : $value;
                if (is_bool($value)) {
                    $filename = $value ? '1' : '0';
                }
                
                if ($filename !== '' && $filename !== null) {
                    \Log::debug("Saving setting: {$key} = {$filename}");
                    
                    $type = in_array($key, ['email_logo', 'email_header_bg_image']) ? 'file' : 'text';
                    if (in_array($key, ['show_logo', 'show_dividers'])) {
                        $type = 'boolean';
                    }
                    
                    SiteSetting::updateOrCreate(
                        ['key' => $key],
                        [
                            'group' => 'email_branding',
                            'type'  => $type,
                            'label' => ucwords(str_replace('_', ' ', $key)),
                            'value' => (string)$filename,
                        ]
                    );

                    if (in_array($key, ['email_logo', 'email_header_bg_image'], true)) {
                        PublicStorageUrl::fromPath($filename);
                    }
                }
            }

            Notification::make()
                ->title('Email branding saved!')
                ->success()
                ->send();
                
            \Log::info('EmailBrandingSettings saved successfully');
        } catch (\Exception $e) {
            \Log::error('EmailBrandingSettings save failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            
            Notification::make()
                ->title('Error saving branding')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save Branding')
                ->action('save')
                ->color('primary'),

            \Filament\Actions\Action::make('preview_email')
                ->label('Preview Email')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn () => route('filament.admin.email-branding.preview'))
                ->openUrlInNewTab(),
        ];
    }
}
