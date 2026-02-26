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
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BrandDocumentSettings extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-swatch';
    protected static ?string $navigationLabel = 'Brand & Documents';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int    $navigationSort  = 18;
    protected static string  $view            = 'filament.pages.brand-document-settings';

    public array $data = [];

    // Keys managed by this form
    protected const FILE_KEYS = ['logo_main', 'logo_pdf', 'brand_header_bg_image', 'brand_signature_image'];
    protected const ALL_KEYS  = [
        'brand_name', 'brand_tagline',
        'logo_main', 'logo_pdf',
        'brand_primary_color', 'brand_accent_color',
        'brand_button_bg', 'brand_button_text',
        'brand_card_radius', 'brand_card_padding',
        'brand_heading_scale', 'brand_logo_size',
        'brand_button_style', 'brand_bg_style',
        'brand_header_bg_image',
        'brand_watermark', 'brand_watermark_opacity',
        'brand_signature_name', 'brand_signature_image',
        'brand_terms_text', 'brand_footer_text',
    ];

    public function mount(): void
    {
        $settings = SiteSetting::whereIn('key', self::ALL_KEYS)->pluck('value', 'key');
        foreach (self::FILE_KEYS as $fileKey) {
            PublicStorageUrl::fromPath($settings->get($fileKey));
        }

        $this->form->fill([
            'brand_name'            => $settings->get('brand_name',            'Khao Sok Silver Cliff'),
            'brand_tagline'         => $settings->get('brand_tagline',          'The Real Jungle Experience'),
            'logo_main'             => $settings->get('logo_main',              '') ?: null,
            'logo_pdf'              => $settings->get('logo_pdf',               '') ?: null,
            'brand_primary_color'   => $settings->get('brand_primary_color',   '#152a10'),
            'brand_accent_color'    => $settings->get('brand_accent_color',    '#C6A84B'),
            'brand_button_bg'       => $settings->get('brand_button_bg',       '#C6A84B'),
            'brand_button_text'     => $settings->get('brand_button_text',     '#152a10'),
            'brand_card_radius'     => $settings->get('brand_card_radius',     '12'),
            'brand_card_padding'    => $settings->get('brand_card_padding',    'md'),
            'brand_heading_scale'   => $settings->get('brand_heading_scale',   'md'),
            'brand_logo_size'       => $settings->get('brand_logo_size',       'md'),
            'brand_button_style'    => $settings->get('brand_button_style',    'solid'),
            'brand_bg_style'        => $settings->get('brand_bg_style',        'gradient'),
            'brand_header_bg_image' => $settings->get('brand_header_bg_image', '') ?: null,
            'brand_watermark'       => (bool) $settings->get('brand_watermark', '1'),
            'brand_watermark_opacity' => (int) ($settings->get('brand_watermark_opacity', '8')),
            'brand_signature_name'  => $settings->get('brand_signature_name',  'Reservations Team'),
            'brand_signature_image' => $settings->get('brand_signature_image', '') ?: null,
            'brand_terms_text'      => $settings->get('brand_terms_text', ''),
            'brand_footer_text'     => $settings->get('brand_footer_text', ''),
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
                Log::warning('Unable to read file metadata for brand upload.', [
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
            $baseName = filled($baseName) ? $baseName : 'brand-asset';

            return "{$baseName}-" . Str::ulid() . '.' . strtolower($file->getClientOriginalExtension());
        };

        return $form
            ->schema([

                // ── Identity ────────────────────────────────────────────────
                Forms\Components\Section::make('Brand Identity')
                    ->icon('heroicon-o-building-office')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('brand_name')
                            ->label('Brand Name')
                            ->required()
                            ->placeholder('Khao Sok Silver Cliff'),

                        Forms\Components\TextInput::make('brand_tagline')
                            ->label('Tagline')
                            ->placeholder('The Real Jungle Experience'),
                    ]),

                // ── Logos ────────────────────────────────────────────────────
                Forms\Components\Section::make('Logos')
                    ->icon('heroicon-o-photo')
                    ->columns(2)
                    ->description('PNG/WebP recommended. Max 10MB. Used across email headers and PDF vouchers.')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_main')
                            ->label('Main Logo (email + website)')
                            ->disk('public')
                            ->directory('brand-assets')
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->fetchFileInformation(false)
                            ->getUploadedFileUsing($resolveUploadedFile)
                            ->getUploadedFileNameForStorageUsing($safeUploadedFileName)
                            ->imagePreviewHeight('80')
                            ->maxSize(10240)
                            ->helperText('Primary logo used everywhere.')
                            ->nullable(),

                        Forms\Components\FileUpload::make('logo_pdf')
                            ->label('PDF Logo (voucher override)')
                            ->disk('public')
                            ->directory('brand-assets')
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->fetchFileInformation(false)
                            ->getUploadedFileUsing($resolveUploadedFile)
                            ->getUploadedFileNameForStorageUsing($safeUploadedFileName)
                            ->imagePreviewHeight('80')
                            ->maxSize(10240)
                            ->helperText('Leave blank to use the Main Logo.')
                            ->nullable(),
                    ]),

                // ── Colors ────────────────────────────────────────────────────
                Forms\Components\Section::make('Brand Colors')
                    ->icon('heroicon-o-paint-brush')
                    ->columns(2)
                    ->schema([
                        Forms\Components\ColorPicker::make('brand_primary_color')
                            ->label('Primary Color (deep jungle green)'),

                        Forms\Components\ColorPicker::make('brand_accent_color')
                            ->label('Accent Color (luxury gold)'),

                        Forms\Components\ColorPicker::make('brand_button_bg')
                            ->label('Button Background'),

                        Forms\Components\ColorPicker::make('brand_button_text')
                            ->label('Button Text Color'),
                    ]),

                // ── Layout Tokens ──────────────────────────────────────────────
                Forms\Components\Section::make('Layout & Sizing')
                    ->icon('heroicon-o-squares-2x2')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('brand_logo_size')
                            ->label('Logo Size')
                            ->options(['sm' => 'Small (48px)', 'md' => 'Medium (64px)', 'lg' => 'Large (84px)'])
                            ->required(),

                        Forms\Components\Select::make('brand_card_padding')
                            ->label('Card Body Padding')
                            ->options(['sm' => 'Compact (20px)', 'md' => 'Default (32px)', 'lg' => 'Spacious (48px)'])
                            ->required(),

                        Forms\Components\Select::make('brand_card_radius')
                            ->label('Card Border Radius')
                            ->options(['8' => 'Subtle (8px)', '12' => 'Rounded (12px)', '16' => 'Soft (16px)'])
                            ->required(),

                        Forms\Components\Select::make('brand_heading_scale')
                            ->label('Heading Size')
                            ->options(['sm' => 'Small (18px)', 'md' => 'Normal (22px)', 'lg' => 'Large (27px)'])
                            ->required(),

                        Forms\Components\Select::make('brand_button_style')
                            ->label('Button Style')
                            ->options(['solid' => 'Solid Fill', 'outline' => 'Outline'])
                            ->required(),
                    ]),

                // ── Header Styling ─────────────────────────────────────────────
                Forms\Components\Section::make('Header Background')
                    ->icon('heroicon-o-sparkles')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('brand_bg_style')
                            ->label('Header Style')
                            ->options([
                                'solid'        => 'Solid Color (primary)',
                                'gradient'     => 'Gradient (Luxury Jungle)',
                                'jungle_image' => 'Background Image',
                            ])
                            ->reactive()
                            ->required(),

                        Forms\Components\FileUpload::make('brand_header_bg_image')
                            ->label('Header Background Image')
                            ->disk('public')
                            ->directory('brand-assets')
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->fetchFileInformation(false)
                            ->getUploadedFileUsing($resolveUploadedFile)
                            ->getUploadedFileNameForStorageUsing($safeUploadedFileName)
                            ->maxSize(5120)
                            ->helperText('Used when style = Background Image.')
                            ->visible(fn ($get) => $get('brand_bg_style') === 'jungle_image')
                            ->nullable(),
                    ]),

                // ── Terms & Footer ─────────────────────────────────────────────
                Forms\Components\Section::make('Text & Terms')
                    ->icon('heroicon-o-document-text')
                    ->columns(1)
                    ->schema([
                        Forms\Components\Textarea::make('brand_terms_text')
                            ->label('Terms & Conditions (PDF Voucher)')
                            ->rows(4)
                            ->helperText('Leave empty to use the default hardcoded PDF policies.'),
                        Forms\Components\Textarea::make('brand_footer_text')
                            ->label('Footer Text (PDF Voucher)')
                            ->rows(2)
                            ->helperText('Leave empty to use the default copyright notice.'),
                    ]),

                // ── PDF Voucher ────────────────────────────────────────────────
                Forms\Components\Section::make('PDF Voucher Specific Settings')
                    ->icon('heroicon-o-document-text')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('brand_watermark')
                            ->label('Show Logo Watermark on Voucher')
                            ->default(true),

                        Forms\Components\TextInput::make('brand_watermark_opacity')
                            ->label('Watermark Opacity (%)')
                            ->numeric()
                            ->minValue(3)
                            ->maxValue(25)
                            ->step(1)
                            ->default(8)
                            ->suffix('%')
                            ->helperText('5–20 recommended for subtle effect.'),

                        Forms\Components\TextInput::make('brand_signature_name')
                            ->label('Signature Line Name')
                            ->placeholder('Reservations Team')
                            ->helperText('Appears on the voucher signature area.'),

                        Forms\Components\FileUpload::make('brand_signature_image')
                            ->label('Signature Image (optional)')
                            ->disk('public')
                            ->directory('brand-assets')
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->fetchFileInformation(false)
                            ->getUploadedFileUsing($resolveUploadedFile)
                            ->getUploadedFileNameForStorageUsing($safeUploadedFileName)
                            ->maxSize(2048)
                            ->nullable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            $type = in_array($key, self::FILE_KEYS) ? 'file'
                  : ($key === 'brand_watermark' ? 'boolean'
                  : ($key === 'brand_watermark_opacity' ? 'number' : 'text'));

            $normalizedValue = is_array($value)
                ? Arr::first($value)
                : ($value === true ? '1' : ($value === false ? '0' : ($value ?? '')));

            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'group' => 'brand',
                    'type'  => $type,
                    'label' => ucwords(str_replace('_', ' ', $key)),
                    'value' => $normalizedValue,
                ]
            );

            if (in_array($key, self::FILE_KEYS, true) && filled($normalizedValue)) {
                PublicStorageUrl::fromPath($normalizedValue);
            }
        }

        // Sync legacy email_branding keys so existing code keeps working
        $syncMap = [
            'email_primary_color'   => $state['brand_primary_color']   ?? null,
            'email_secondary_color' => '#2a5220',
            'email_button_bg'       => $state['brand_button_bg']        ?? null,
            'email_button_text'     => $state['brand_button_text']      ?? null,
            'email_logo_size'       => $state['brand_logo_size']        ?? null,
            'email_card_padding'    => $state['brand_card_padding']     ?? null,
            'email_border_radius'   => $state['brand_card_radius']      ?? null,
            'email_heading_scale'   => $state['brand_heading_scale']    ?? null,
            'email_header_style'    => $state['brand_bg_style']         ?? null,
        ];
        foreach ($syncMap as $k => $v) {
            if ($v !== null) {
                SiteSetting::where('key', $k)->update(['value' => $v]);
            }
        }

        Notification::make()->title('Brand settings saved!')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('preview_pdf')
                ->label('Preview PDF Voucher')
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('info')
                ->url(fn () => route('filament.admin.voucher.preview'))
                ->openUrlInNewTab(),

            \Filament\Actions\Action::make('test_email')
                ->label('Test Conf Email')
                ->icon('heroicon-o-envelope')
                ->color('warning')
                ->form([
                    Forms\Components\TextInput::make('test_email')
                        ->label('Recipient Email')
                        ->email()
                        ->required()
                        ->default(auth()->check() ? auth()->user()->email : 'test@example.com'),
                ])
                ->action(function (array $data) {
                    try {
                        $controller = app(\App\Http\Controllers\Admin\VoucherPreviewController::class);
                        $method = new \ReflectionMethod($controller, 'fakeDemoBooking');
                        $method->setAccessible(true);
                        
                        $booking = \App\Models\Booking::with(['roomDetail', 'tourDetail.activity', 'packageDetail.package', 'packageOptions'])->latest()->first() ?? $method->invoke($controller);
                        
                        $mail = clone \App\Mail\TemplatedMail::make('booking_confirmation_guest', clone $booking, true);
                        if (!$mail) {
                            throw new \Exception('booking_confirmation_guest template not found or disabled.');
                        }
                        
                        \Illuminate\Support\Facades\Mail::to($data['test_email'])->send($mail);
                        \Filament\Notifications\Notification::make()->title('Test email sent!')->success()->send();
                        \Illuminate\Support\Facades\Log::info("Test confirmation email sent to {$data['test_email']}");
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Test confirmation email failed: ' . $e->getMessage());
                        \Filament\Notifications\Notification::make()->title('Failed to send')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
