<!-- Transfer Money Modal -->
<div class="modal fade" id="transferMoneyModal" tabindex="-1" aria-labelledby="transferMoneyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-simple">
    <div class="modal-content p-2 p-md-4">
      <div class="modal-body position-relative">
        <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
        
        <!-- Header -->
        <div class="text-center mb-4">
          <div class="avatar avatar-xl mx-auto mb-3 bg-label-warning rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 64px; height: 64px;">
            <i class="icon-base ti tabler-arrows-exchange text-warning" style="font-size: 32px;"></i>
          </div>
          <h3 class="mb-1 fw-bold" id="transferMoneyModalLabel">{{ __('Transfer Money') }}</h3>
          <p class="text-body-secondary mb-0">{{ __('Transfer gold and silver between your characters or manage funds with the Guild Vault.') }}</p>
        </div>

        <!-- Transfer Type Tabs -->
        <div class="nav-align-top mb-4">
          <ul class="nav nav-pills nav-fill" role="tablist">
            <li class="nav-item">
              <button type="button" class="nav-link active d-flex align-items-center justify-content-center gap-2 py-2" role="tab" data-bs-toggle="tab" data-bs-target="#tab-transfer-characters" aria-selected="true" id="btn-tab-transfer-char">
                <i class="icon-base ti tabler-users text-primary"></i>
                <span class="fw-semibold">{{ __('Between Characters') }}</span>
              </button>
            </li>
            <li class="nav-item">
              <button type="button" class="nav-link d-flex align-items-center justify-content-center gap-2 py-2" role="tab" data-bs-toggle="tab" data-bs-target="#tab-transfer-vault" aria-selected="false" id="btn-tab-transfer-vault">
                <i class="icon-base ti tabler-building-bank text-warning"></i>
                <span class="fw-semibold">{{ __('Guild Vault / Inventory') }}</span>
              </button>
            </li>
          </ul>
        </div>

        <div class="tab-content p-0">
          <!-- Tab 1: Between Characters -->
          <div class="tab-pane fade show active" id="tab-transfer-characters" role="tabpanel">
            <div id="transferCharNeedMoreAlert" class="alert alert-info d-none d-flex align-items-center justify-content-between mb-4">
              <div class="d-flex align-items-center">
                <i class="icon-base ti tabler-info-circle me-2 fs-4"></i>
                <span>{{ __('You need at least 2 characters to transfer money between heroes.') }}</span>
              </div>
              <button type="button" class="btn btn-sm btn-primary" id="btnQuickCreateCompanion">
                <i class="icon-base ti tabler-plus me-1"></i> {{ __('Create Companion Hero') }}
              </button>
            </div>

            <form id="transferBetweenCharactersForm" onsubmit="return false;">
              <div class="row g-3">
                <!-- Source Character -->
                <div class="col-12 col-md-6">
                  <label class="form-label fw-semibold" for="transferFromCharSelect">
                    <i class="icon-base ti tabler-arrow-up-right text-danger me-1"></i> {{ __('Sender Character (From)') }}
                  </label>
                  <select class="form-select form-select-lg" id="transferFromCharSelect" required>
                    <!-- Populated via JS -->
                  </select>
                  <div class="form-text small text-body-secondary" id="transferFromBalanceHint">{{ __('Current Balance') }}: 0 GP</div>
                </div>

                <!-- Destination Character -->
                <div class="col-12 col-md-6">
                  <label class="form-label fw-semibold" for="transferToCharSelect">
                    <i class="icon-base ti tabler-arrow-down-left text-success me-1"></i> {{ __('Recipient Character (To)') }}
                  </label>
                  <select class="form-select form-select-lg" id="transferToCharSelect" required>
                    <!-- Populated via JS -->
                  </select>
                  <div class="form-text small text-body-secondary" id="transferToBalanceHint">{{ __('Current Balance') }}: 0 GP</div>
                </div>

                <!-- Amount & Currency -->
                <div class="col-12 col-md-7">
                  <label class="form-label fw-semibold" for="transferCharAmountInput">
                    <i class="icon-base ti tabler-coins text-warning me-1"></i> {{ __('Amount to Transfer') }}
                  </label>
                  <div class="input-group input-group-merge">
                    <span class="input-group-text"><i class="icon-base ti tabler-cash"></i></span>
                    <input type="number" id="transferCharAmountInput" class="form-control form-control-lg" placeholder="10" min="1" step="1" required />
                  </div>
                </div>

                <div class="col-12 col-md-5">
                  <label class="form-label fw-semibold" for="transferCharCurrencySelect">
                    <i class="icon-base ti tabler-coin me-1"></i> {{ __('Currency') }}
                  </label>
                  <select class="form-select form-select-lg" id="transferCharCurrencySelect">
                    <option value="GP" selected>GP (Gold Piece / Altın)</option>
                    <option value="SP">SP (Silver Piece / Gümüş)</option>
                  </select>
                </div>

                <!-- Notes / Purpose -->
                <div class="col-12">
                  <label class="form-label fw-semibold" for="transferCharNotesInput">
                    <i class="icon-base ti tabler-note me-1"></i> {{ __('Transfer Note / Reason (Optional)') }}
                  </label>
                  <input type="text" id="transferCharNotesInput" class="form-control" placeholder="{{ __('e.g., Equipment sharing, potion purchase, mission share') }}" maxlength="100" />
                </div>

                <!-- Live Preview Card -->
                <div class="col-12">
                  <div class="card border border-primary border-opacity-25 bg-label-primary bg-opacity-10 p-3 shadow-none">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                      <div class="d-flex align-items-center gap-2">
                        <i class="icon-base ti tabler-calculator fs-4 text-primary"></i>
                        <span class="fw-semibold text-heading">{{ __('Transfer Summary') }}:</span>
                      </div>
                      <div class="text-end small">
                        <span id="transferCharPreviewText" class="fw-bold text-primary">{{ __('Enter amount to see preview') }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="col-12 text-end pt-2">
                  <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                  <button type="submit" class="btn btn-primary" id="btnSubmitCharTransfer">
                    <i class="icon-base ti tabler-send me-1"></i> {{ __('Send Transfer') }}
                  </button>
                </div>
              </div>
            </form>
          </div>

          <!-- Tab 2: Guild Vault / Inventory -->
          <div class="tab-pane fade" id="tab-transfer-vault" role="tabpanel">
            <form id="transferVaultForm" onsubmit="return false;">
              <div class="row g-3">
                <!-- Operation Direction -->
                <div class="col-12">
                  <label class="form-label fw-semibold d-block">
                    <i class="icon-base ti tabler-arrows-sort text-primary me-1"></i> {{ __('Operation Type') }}
                  </label>
                  <div class="row g-2">
                    <div class="col-6">
                      <div class="form-check custom-option custom-option-basic p-3 rounded border">
                        <label class="form-check-label custom-option-content d-flex align-items-center gap-2 cursor-pointer" for="optVaultWithdraw">
                          <input name="vaultOperationType" class="form-check-input" type="radio" value="withdraw" id="optVaultWithdraw" checked />
                          <div>
                            <span class="fw-bold text-success d-block"><i class="icon-base ti tabler-arrow-down-left me-1"></i> {{ __('Withdraw from Vault') }}</span>
                            <small class="text-body-secondary">{{ __('Vault -> Character') }}</small>
                          </div>
                        </label>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="form-check custom-option custom-option-basic p-3 rounded border">
                        <label class="form-check-label custom-option-content d-flex align-items-center gap-2 cursor-pointer" for="optVaultDeposit">
                          <input name="vaultOperationType" class="form-check-input" type="radio" value="deposit" id="optVaultDeposit" />
                          <div>
                            <span class="fw-bold text-warning d-block"><i class="icon-base ti tabler-arrow-up-right me-1"></i> {{ __('Deposit to Vault') }}</span>
                            <small class="text-body-secondary">{{ __('Character -> Vault') }}</small>
                          </div>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Vault Reserve Indicator -->
                <div class="col-12">
                  <div class="card border border-warning border-opacity-25 bg-label-warning bg-opacity-10 p-3 shadow-none">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                      <div class="d-flex align-items-center gap-2">
                        <i class="icon-base ti tabler-building-bank fs-4 text-warning"></i>
                        <div>
                          <span class="fw-bold text-heading">{{ __('Guild Vault Reserve') }} ({{ $vault->name ?? 'Ana Lonca Kasası' }}):</span>
                          <div class="small text-body-secondary">{{ $vault->notes ?? __('Guild liquid funds') }}</div>
                        </div>
                      </div>
                      <div class="text-end">
                        <span class="badge bg-warning text-dark fs-6 font-monospace" id="modalVaultBalanceSp">
                          {{ number_format((float) ($vault->balance_sp ?? 358989.50), 0, ',', '.') }} SP
                        </span>
                        <div class="small text-muted font-monospace" id="modalVaultBalanceGp">
                          ~{{ number_format(((float) ($vault->balance_sp ?? 358989.50)) / 10, 1, ',', '.') }} GP
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Character -->
                <div class="col-12 col-md-6">
                  <label class="form-label fw-semibold" for="transferVaultCharSelect">
                    <i class="icon-base ti tabler-user text-primary me-1"></i> {{ __('Selected Character') }}
                  </label>
                  <select class="form-select form-select-lg" id="transferVaultCharSelect" required>
                    <!-- Populated via JS -->
                  </select>
                  <div class="form-text small text-body-secondary" id="transferVaultCharBalanceHint">{{ __('Current Balance') }}: 0 GP</div>
                </div>

                <!-- Amount & Currency -->
                <div class="col-12 col-md-3">
                  <label class="form-label fw-semibold" for="transferVaultAmountInput">
                    <i class="icon-base ti tabler-cash text-warning me-1"></i> {{ __('Amount') }}
                  </label>
                  <input type="number" id="transferVaultAmountInput" class="form-control form-control-lg" placeholder="25" min="1" step="1" required />
                </div>

                <div class="col-12 col-md-3">
                  <label class="form-label fw-semibold" for="transferVaultCurrencySelect">
                    <i class="icon-base ti tabler-coin me-1"></i> {{ __('Currency') }}
                  </label>
                  <select class="form-select form-select-lg" id="transferVaultCurrencySelect">
                    <option value="GP" selected>GP (Gold / Altın)</option>
                    <option value="SP">SP (Silver / Gümüş)</option>
                  </select>
                </div>

                <!-- Notes / Purpose -->
                <div class="col-12">
                  <label class="form-label fw-semibold" for="transferVaultNotesInput">
                    <i class="icon-base ti tabler-note me-1"></i> {{ __('Vault Transaction Reason (Optional)') }}
                  </label>
                  <input type="text" id="transferVaultNotesInput" class="form-control" placeholder="{{ __('e.g., Guild mission allowance, expedition fund, donation') }}" maxlength="100" />
                </div>

                <!-- Live Preview Card -->
                <div class="col-12">
                  <div class="card border border-warning border-opacity-25 bg-label-warning bg-opacity-10 p-3 shadow-none">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                      <div class="d-flex align-items-center gap-2">
                        <i class="icon-base ti tabler-calculator fs-4 text-warning"></i>
                        <span class="fw-semibold text-heading">{{ __('Vault Transaction Preview') }}:</span>
                      </div>
                      <div class="text-end small">
                        <span id="transferVaultPreviewText" class="fw-bold text-warning">{{ __('Enter amount to preview changes') }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="col-12 text-end pt-2">
                  <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                  <button type="submit" class="btn btn-warning text-dark fw-bold" id="btnSubmitVaultTransfer">
                    <i class="icon-base ti tabler-building-bank me-1"></i> {{ __('Execute Vault Transfer') }}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
