<x-filament-panels::page>
    <div style="display:flex;flex-direction:column;gap:22px;">
        <section style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap;">
            <div>
                <p style="margin:0;max-width:760px;font-size:15px;line-height:1.7;color:#64748b;">
                    Review verification requests without an assigned owner and assign them to the right verification teammate from one place.
                </p>
            </div>
        </section>

        <section style="border:1px solid #e5e7eb;border-radius:24px;background:#ffffff;overflow:hidden;box-shadow:0 8px 24px rgba(15,23,42,0.06);">
            <div style="padding:18px 20px;">
                {{ $this->table }}
            </div>
        </section>
    </div>
</x-filament-panels::page>
