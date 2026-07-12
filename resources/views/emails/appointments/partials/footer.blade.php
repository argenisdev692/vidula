{{-- Expects $company (Shared\Infrastructure\Company\CompanyProfile::data()). --}}
<div class="footer">
    @if (! empty($company['support_email']))
        <p class="contact-line"><a href="mailto:{{ $company['support_email'] }}">{{ $company['support_email'] }}</a></p>
    @endif
    @if (! empty($company['address']))
        <p class="contact-line">{{ $company['address'] }}</p>
    @endif
    <p class="contact-line">&copy; {{ date('Y') }} {{ $company['name'] }}. All rights reserved.</p>
</div>
