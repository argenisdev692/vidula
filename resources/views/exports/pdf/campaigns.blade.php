@extends('exports.pdf.layout')

@section('report_heading', 'Meta Ads Campaigns')
@section('report_subtitle', 'AI-generated lead-gen campaign copy, funnel stage and success-probability scoring.')

@section('content')
    <table class="data-table">
        <thead>
            <tr>
                <th>Topic</th>
                <th>Funnel Stage</th>
                <th>Platform</th>
                <th>Status</th>
                <th>Success Probability</th>
                <th>Scores Passed</th>
                <th>Created</th>
                <th>Suspended</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['Topic'] }}</td>
                    <td>{{ $row['Funnel Stage'] }}</td>
                    <td>{{ $row['Platform'] }}</td>
                    <td>{{ $row['Status'] }}</td>
                    <td>{{ $row['Success Probability'] }}</td>
                    <td>{{ $row['Scores Passed'] }}</td>
                    <td>{{ $row['Created'] }}</td>
                    <td>{{ $row['Suspended'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state">No campaigns to display.</div></td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
