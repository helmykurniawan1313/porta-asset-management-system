<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $employee->name }} — Review Report</title>
    <style>
        @page { margin: 24px 28px; }

        * { box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5px;
            line-height: 1.35;
            color: #221F1A;
        }

        h1 {
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 2px 0;
        }

        .subtitle {
            font-size: 8px;
            color: #766B57;
            margin: 0 0 14px 0;
        }

        .section {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 10px;
            font-weight: 700;
            margin: 0 0 1px 0;
        }

        .section-meta {
            font-size: 7.5px;
            color: #766B57;
            margin: 0 0 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th, td {
            border: 1px solid #E7E1D6;
            padding: 2.5px 5px;
            text-align: left;
            font-size: 8px;
        }

        th {
            background-color: #F5F1E9;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 7px;
            letter-spacing: 0.02em;
        }

        td.center, th.center {
            text-align: center;
        }

        td.average {
            font-weight: 700;
        }

        .text-question {
            font-weight: 700;
            font-size: 8px;
            margin: 6px 0 2px 0;
        }

        .text-answer {
            border: 1px solid #E7E1D6;
            background-color: #FBF8F3;
            padding: 3px 5px;
            margin-bottom: 3px;
            border-radius: 2px;
        }

        .text-answer .reviewer {
            font-weight: 700;
            font-size: 7px;
            text-transform: uppercase;
            color: #766B57;
            margin: 0 0 1px 0;
        }

        .text-answer .answer {
            font-size: 8px;
            margin: 0;
        }

        .no-data {
            font-size: 8.5px;
            color: #766B57;
        }
    </style>
</head>
<body>
    <h1>Review Report — {{ $employee->name }}</h1>
    <p class="subtitle">{{ $employee->email }}</p>

    @if ($sections->isEmpty())
        <p class="no-data">No submitted reviews yet for {{ $employee->name }}.</p>
    @endif

    @foreach ($sections as $section)
        @php
            $typeLabels = ['self' => 'Self Review', 'peer' => 'Peer Review', 'upward' => 'Upward Review', 'downward' => 'Downward Review'];
        @endphp
        <div class="section">
            <p class="section-title">{{ $typeLabels[$section['type']] ?? $section['type'] }}</p>
            <p class="section-meta">{{ count($section['reviewers']) }} reviewer{{ count($section['reviewers']) === 1 ? '' : 's' }}</p>

            @if (count($section['ratingRows']) > 0)
                <table>
                    <thead>
                        <tr>
                            <th style="width: 26%;">Criteria</th>
                            @foreach ($section['reviewers'] as $reviewer)
                                <th class="center">{{ $reviewer['name'] }}</th>
                            @endforeach
                            <th class="center">Avg</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($section['ratingRows'] as $row)
                            <tr>
                                <td>{{ $row['criteria'] }}</td>
                                @foreach ($row['scores'] as $score)
                                    <td class="center">{{ $score ?? '—' }}</td>
                                @endforeach
                                <td class="center average">{{ $row['average'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @foreach ($section['textAnswers'] as $textQuestion)
                <p class="text-question">{{ $textQuestion['question'] }}</p>
                @foreach ($textQuestion['answers'] as $answer)
                    <div class="text-answer">
                        <p class="reviewer">{{ $answer['reviewer'] }}</p>
                        <p class="answer">{{ $answer['answer'] }}</p>
                    </div>
                @endforeach
            @endforeach
        </div>
    @endforeach
</body>
</html>
