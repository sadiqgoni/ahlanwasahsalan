<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('receipt_no')
                    ->label('Receipt #')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('d M, h:i A')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Cashier'),
                TextColumn::make('items_summary')
                    ->label('Items')
                    ->state(fn ($record): string => $record->items->map(
                        fn ($i) => $i->quantity.'x '.$i->product_name
                    )->implode(', '))
                    ->limit(45),
                TextColumn::make('total')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('payment_method')
                    ->label('Paid via')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        default => 'warning',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'voided' ? 'danger' : 'success'),
            ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'transfer' => 'Transfer',
                        'pos' => 'POS Card',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'voided' => 'Voided',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('reprint')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record): string => route('receipt.print', $record))
                    ->openUrlInNewTab(),
                Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record): bool => $record->status === 'completed')
                    ->schema([
                        TextInput::make('pin')
                            ->label('Owner PIN')
                            ->password()
                            ->required(),
                        TextInput::make('reason')
                            ->label('Reason for voiding')
                            ->required(),
                    ])
                    ->action(function ($record, array $data): void {
                        $owner = User::where('role', 'owner')->where('is_active', true)->get()
                            ->first(fn (User $u) => $u->pin && Hash::check($data['pin'], $u->pin));

                        if (! $owner) {
                            Notification::make()
                                ->title('Wrong PIN — void refused')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => 'voided',
                            'voided_at' => now(),
                            'voided_by' => $owner->id,
                            'void_reason' => $data['reason'],
                        ]);

                        Notification::make()
                            ->title("Receipt {$record->receipt_no} voided")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
