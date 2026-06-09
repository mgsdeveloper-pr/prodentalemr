<x-filament-panels::page>
    <div style="position: relative; min-height: 68vh;">
        <div style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.28); z-index: 40;"></div>

        <section style="position: relative; z-index: 50; display: flex; justify-content: center; padding: 48px 16px;">
            <div style="width: min(560px, 100%); border: 1px solid #dbe4ee; border-radius: 26px; background: #ffffff; box-shadow: 0 32px 70px rgba(15, 23, 42, 0.18); overflow: hidden;">
                <div style="padding: 20px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;">
                    <div>
                        <div style="display: inline-flex; align-items: center; padding: 6px 11px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase;">
                            Access Management
                        </div>
                        <h2 style="margin: 12px 0 0; font-size: 28px; font-weight: 800; color: #0f172a;">Assign Clinic</h2>
                        <p style="margin: 8px 0 0; font-size: 14px; line-height: 1.7; color: #64748b;">
                            Select a user and clinic, then choose whether to grant or remove access.
                        </p>
                    </div>

                    <a href="{{ $this->closeUrl() }}" style="display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 20px; text-decoration: none;">&times;</a>
                </div>

                <div style="padding: 22px;">
                    <form wire:submit="save" style="display: flex; flex-direction: column; gap: 18px;">
                        {{ $this->form }}

                        <div style="display: flex; justify-content: flex-end; gap: 10px;">
                            <a href="{{ $this->closeUrl() }}" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700; text-decoration: none;">Close</a>
                            <button type="submit" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 16px; border-radius: 14px; border: 0; background: linear-gradient(180deg, #14b8a6 0%, #0f766e 100%); color: #ffffff; font-size: 13px; font-weight: 800;">
                                Apply
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
