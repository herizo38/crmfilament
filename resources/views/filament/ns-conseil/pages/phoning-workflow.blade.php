{{-- resources/views/filament/ns-conseil/pages/phoning-workflow.blade.php --}}
<x-filament-panels::page>

    @if($currentContact)
            @php $info = $this->getContactInfo(); @endphp

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start;">

                {{-- Colonne gauche --}}
                <div style="display:flex; flex-direction:column; gap:1rem;">

                    {{-- Progression + badges --}}
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span
                            style="background:var(--color-background-secondary); border:0.5px solid var(--color-border-tertiary); border-radius:var(--border-radius-md); padding:4px 10px; font-size:13px; color:var(--color-text-secondary);">
                            {{ $progress }}% complété
                        </span>
                        <span
                            style="background:var(--color-background-secondary); border:0.5px solid var(--color-border-tertiary); border-radius:var(--border-radius-md); padding:4px 10px; font-size:13px;
                            color:{{ $contactType === 'artisan' ? 'var(--color-text-warning)' : ($contactType === 'partenaire' ? 'var(--color-text-info)' : 'var(--color-text-success)') }};">
                            {{ $contactType === 'artisan' ? 'Artisan' : ($contactType === 'partenaire' ? 'Partenaire' : 'Particulier') }}
                        </span>
                        @if($info['priorite'] ?? null)
                            <span
                                style="background:var(--color-background-secondary); border:0.5px solid var(--color-border-tertiary); border-radius:var(--border-radius-md); padding:4px 10px; font-size:13px; color:var(--color-text-secondary);">
                                Priorité {{ $info['priorite'] }}
                            </span>
                        @endif
                    </div>

                    {{-- Fiche contact --}}
                    <div
                        style="background:var(--color-background-primary); border:0.5px solid var(--color-border-tertiary); border-radius:var(--border-radius-lg); overflow:hidden;">
                        <div
                            style="padding:12px 16px; border-bottom:0.5px solid var(--color-border-tertiary); display:flex; align-items:center; gap:10px;">
                            <div
                                style="width:36px; height:36px; border-radius:50%; background:var(--color-background-info); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:500; color:var(--color-text-info); flex-shrink:0;">
                                {{ strtoupper(substr($info['prenom'] ?? 'C', 0, 1) . substr($info['nom'] ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <p style="font-weight:500; font-size:15px; margin:0; color:var(--color-text-primary);">
                                    {{ trim(($info['prenom'] ?? '') . ' ' . ($info['nom'] ?? '')) ?: '—' }}
                                </p>
                                @if($info['metier'] ?? null)
                                    <p style="font-size:12px; color:var(--color-text-secondary); margin:0;">{{ $info['metier'] }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div style="padding:0 16px;">
                            <table style="width:100%; font-size:13px; border-collapse:collapse;">
                                <tr style="border-bottom:0.5px solid var(--color-border-tertiary);">
                                    <td style="padding:10px 0; color:var(--color-text-secondary); width:40%;">
                                        <i class="ti ti-phone" style="font-size:14px; vertical-align:-2px; margin-right:6px;"
                                            aria-hidden="true"></i>Téléphone
                                    </td>
                                    <td
                                        style="padding:10px 0; text-align:right; font-family:var(--font-mono); font-weight:500; font-size:14px; color:var(--color-text-primary);">
                                        {{ $info['telephone'] ?? '—' }}
                                    </td>
                                </tr>
                                <tr style="border-bottom:0.5px solid var(--color-border-tertiary);">
                                    <td style="padding:10px 0; color:var(--color-text-secondary);">
                                        <i class="ti ti-flag" style="font-size:14px; vertical-align:-2px; margin-right:6px;"
                                            aria-hidden="true"></i>Statut
                                    </td>
                                    <td style="padding:10px 0; text-align:right; color:var(--color-text-primary);">
                                        {{ $info['statut'] ?? '—' }}
                                    </td>
                                </tr>
                                @if($info['email'] ?? null)
                                    <tr>
                                        <td style="padding:10px 0; color:var(--color-text-secondary);">
                                            <i class="ti ti-mail" style="font-size:14px; vertical-align:-2px; margin-right:6px;"
                                                aria-hidden="true"></i>Email
                                        </td>
                                        <td style="padding:10px 0; text-align:right; color:var(--color-text-info); font-size:12px;">
                                            {{ $info['email'] }}
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <button wire:click="callNow"
                            style="width:100%; padding:11px 16px; background:var(--color-background-success); border:0.5px solid var(--color-border-success); border-radius:var(--border-radius-md); color:var(--color-text-success); font-size:14px; font-weight:500; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
                            <i class="ti ti-phone-outgoing" style="font-size:16px;" aria-hidden="true"></i>
                            Appeler maintenant
                        </button>
                        <button wire:click="skipCall"
                            style="width:100%; padding:11px 16px; background:var(--color-background-secondary); border:0.5px solid var(--color-border-tertiary); border-radius:var(--border-radius-md); color:var(--color-text-secondary); font-size:14px; font-weight:500; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
                            <i class="ti ti-player-skip-forward" style="font-size:16px;" aria-hidden="true"></i>
                            Passer au suivant
                        </button>
                    </div>
                </div>

                {{-- Colonne droite — Qualification --}}
                <div
                    style="background:var(--color-background-primary); border:0.5px solid var(--color-border-tertiary); border-radius:var(--border-radius-lg); overflow:hidden;">
                    <div style="padding:12px 16px; border-bottom:0.5px solid var(--color-border-tertiary);">
                        <p style="font-size:13px; font-weight:500; margin:0; color:var(--color-text-primary);">Qualification de
                            l'appel</p>
                    </div>

                    <form wire:submit="submitResult" style="padding:16px; display:flex; flex-direction:column; gap:16px;">

                        {{-- Résultat --}}
                        <div>
                            <p style="font-size:12px; color:var(--color-text-secondary); margin:0 0 8px;">Résultat de l'appel *
                            </p>
                            <div style="display:flex; flex-direction:column; gap:6px;">

                                @foreach([
                                                    ['value' => 'qualifie', 'label' => 'Qualifié — intéressé', 'icon' => 'ti-circle-check', 'color' => 'success'],
                                                    ['value' => 'a_relancer', 'label' => 'À relancer', 'icon' => 'ti-phone-call', 'color' => 'warning'],
                                                    ['value' => 'non_joignable', 'label' => 'Non joignable', 'icon' => 'ti-phone-off', 'color' => 'danger'],
                                                    ['value' => 'rappel', 'label' => 'Rappel à programmer', 'icon' => 'ti-clock', 'color' => 'info'],
                                                ] as $option)
                                                <label style="display:flex; align-items
                                     :                  center; gap:10px; padding:10px 12px; border-radius:var(--border-radius-
                                            m           d); cursor:pointer;
                                                    border:{{ $statut_resultat === $option['value'] ? '1.5px' : '0.5px' }} solid var(--color-border-{{ $option['color'] }});
                                                    background:{{ $statut_resultat === $option['value'] ? 'var(--color-background-' . $option['color'] . ')' : 'transparent' }};">
                                                    <input type="radio" wire:model="statut_resultat" value="{{ $option['value'] }}"
                                                        style="width:14px; height:14px; accent-color:currentColor; flex-shrink:0;">
                                                    <i class="ti {{ $option['icon'] }}" style="font-size:15px; color:var(--color-text-{{ $option['color'] }});" aria-hidden="true"></i>
                                                    <span style="font-size:13px; color:var(--color-text-primary);">{{ $option['label'] }}</span>
                                            </label>
                                @endforeach

                            </div>
                            @error('statut_resultat')
                                <p style="font-size:12px; color:var(--color-text-danger); margin:6px 0 0;">{{ $message }}</p>
                            @enderror
                        </div>



                                                   {{-- Date rappel --}}
                        @if($statut_resultat === 'rappel')
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                                <div>

                                                               <label style="font-size:12px; color:var(--color-text-secondary); display:block; margin-bottom:4px;">Date de rappel</label>
                                    <input type="date" wire:model="rappel_date" style="width:100%;">
                                </div>
                                <div>
                                    <label style="font-size:12px; color:var(--color-text-secondary); display:block; margin-bottom:4px;">Heure</label>
                                    <input type="time" wire:model="rappel_heure" style="width:100%;">
                                </div>
                            </div>

                           @endif

                        {{-- Commentaires --}}
                        <div>
                            <label style="font-size:12px; color:var(--color-text-secondary); display:block; margin-bottom:4px;">Commentaires</label>
                            <textarea wire:model="commentaires" rows="3"
                                placeholder="Points à retenir, informations complémentaires…"
                                style="width:100%; resize:none; font-size:13px;"></textarea>
                        </div>

                        <button type="submit"
                            style="width:100%; padding:10px 16px; background:var(--color-background-primary); border:0.5px solid var(--color-border-primary); border-radius:var(--border-radius-md); color:var(--color-text-primary); font-size:14px; font-weight:500; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
                            <i class="ti ti-arrow-right" style="font-size:16px;" aria-hidden="true"></i>
                            Valider et suivant
                        </button>

                    </form>
                </div>
            </div>

       @else
                    <div style="background:var(--color-background-primary); border:0.5px solid var(--color-border-tertiary); border-radius:var(--border-radius-lg); padding:3rem 2rem; text-align:center; max-width:480px; margin:2rem auto;">
                <div style="width:48px; height:48px; border-radius:50%; background:var(--color-background-secondary); display
             :      flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
                    <i class="ti ti-phone-off" style="font-size:22px; color:var(--color-text-secondary);" aria-hidden="true"></i>
                </div>
                <p style="font-size:16px; font-weight:500; margin:0 0 6px; color:var(--color-text-primary);">Aucun contact à appeler</p>
                <p style="font-size:13px; color:var(--color-text-secondary); margin:0 0 1.5rem; line-height:1.6;">
                    Vous n'avez pas encore de contacts dans votre campagne. Ajoutez des artisans, partenaires ou particuliers depuis leur interface respective.
                </p>
                <button wire:click="$refresh"
                    style="padding:9px 20px; background:var(--color-background-secondary); border:0.5px solid var(--color-border-tertiary); border-radius:var(--border-radius-md); color:var(--color-text-primary); font-size:13px; font-weight:500; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                        <i class="ti ti-refresh" style="font-size:15px;" aria-hidden="true"></i>
                    Rafraîchir
                </button>
            </div>

        @endif

</x-filament-panels::page>