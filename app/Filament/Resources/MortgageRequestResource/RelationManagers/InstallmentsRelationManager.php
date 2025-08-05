<?php

namespace App\Filament\Resources\MortgageRequestResource\RelationManagers;

use App\Models\MortgageRequest;
use Filament\Forms;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Installments')
                        ->schema([
                            Forms\Components\TextInput::make('no_of_payment')
                                ->label('No. Payment')
                                ->helperText('Enter the number of payments for this installment')
                                ->required()
                                ->numeric(),
                            Forms\Components\Select::make('sub_total_amount')
                                ->label('Monthly Amount')
                                ->options(function () {
                                    $mortageRequest = $this->getOwnerRecord();
                                    return $mortageRequest ? [$mortageRequest->monthly_amount => $mortageRequest->monthly_amount] : [];
                                })
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $tax = $state * 0.11; //11% tax
                                    $sub_total_amount = $state;
                                    $insurance = 900000; // Fixed insurance amount
                                    $grandTotal = $state + $tax + $insurance;

                                    $set('total_tax_amount', round($tax));
                                    $set('insurance_amount', $insurance);
                                    $set('grand_total_amount', round($grandTotal));

                                    $mortgageRequest = $this->getOwnerRecord();
                                    if ($mortgageRequest) {
                                        $lastInstallment = $mortgageRequest->installments()->where('is_paid', true)->orderBy('no_of_payment', 'desc')->first();
                                        $previousRemainingLoan = $lastInstallment ? $lastInstallment->remaining_loan_mount : $mortgageRequest->loan_interest_total_amount;
                                        $remainingLoanAfterPayment = max($previousRemainingLoan - round($sub_total_amount), 0);

                                        $set('remaining_loan_amount', $remainingLoanAfterPayment);
                                        $set('remaining_loan_amount_before_payment', $previousRemainingLoan);
                                    }
                                }),
                            Forms\Components\TextInput::make('total_tax_amount')
                                ->label('Tax 11%')
                                ->readOnly()
                                ->required()
                                ->numeric()
                                ->prefix('IDR'),
                            Forms\Components\TextInput::make('insurance_amount')
                                ->label('Additional Amount')
                                ->readOnly()
                                ->numeric()
                                ->default(900000)
                                ->prefix('IDR'),
                            Forms\Components\TextInput::make('grand_total_amount')
                                ->label('Total Payment')
                                ->readOnly()
                                ->required()
                                ->numeric()
                                ->prefix('IDR'),
                            Forms\Components\TextInput::make('remaining_loan_amount_before_payment')
                                ->label('Remaining Loan Amount Before Payment')
                                ->readOnly()
                                ->numeric()
                                ->prefix('IDR'),
                            Forms\Components\TextInput::make('remaining_loan_amount')
                                ->label('Remaining Loan Amount After Payment')
                                ->readOnly()
                                ->numeric()
                                ->prefix('IDR'),
                        ]),
                    Forms\Components\Wizard\Step::make('Payment Method')
                        ->schema([
                            ToggleButtons::make('is_paid')
                                ->label('Payment Status')
                                ->boolean()
                                ->grouped()
                                ->icons([
                                    true => 'heroicon-o-check-circle',
                                    false => 'heroicon-o-x-circle',
                                ])
                                ->required(),
                            Forms\Components\Select::make('payment_type')
                                ->label('Payment Type')
                                ->options([
                                    'Midtrans' => 'Midtrans',
                                    'Manual' => 'Manual',
                                ])
                                ->required(),
                            Forms\Components\FileUpload::make('proof')
                                ->label('Payment Proof')
                                ->image(),
                        ]),
                ])
                    ->columnSpan('full')
                    ->columns(1)
                    ->skippable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('no_of_payment')
            ->columns([
                Tables\Columns\TextColumn::make('no_of_payment'),
                Tables\Columns\TextColumn::make('sub_total_amount'),
                Tables\Columns\TextColumn::make('insurance_amount'),
                Tables\Columns\TextColumn::make('total_tax_amount'),
                Tables\Columns\IconColumn::make('is_paid')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->label('Verified'),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
