<div class="pd-intake-summary">
    @foreach (['Patient' => $patient, 'Appointment' => $appointment] as $heading => $details)
        <section>
            <h3>{{ $heading }}</h3>
            <dl>
                @foreach ($details as $label => $value)
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd>{{ filled($value) ? $value : 'Not provided' }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endforeach
</div>
