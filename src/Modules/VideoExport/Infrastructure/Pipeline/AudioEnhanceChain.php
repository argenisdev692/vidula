<?php

declare(strict_types=1);

namespace Modules\VideoExport\Infrastructure\Pipeline;

/** Nest-parity spoken-voice audio DSP chain for ffmpeg. */
final readonly class AudioEnhanceChain
{
    public function build(): string
    {
        $nrAmount = 12;
        $afftdnNf = -25;
        $adeclickAmplitude = 2.0;
        $highpassHz = 120;
        $eqHarshCutDb = -2;
        $gateThreshold = $this->dbToLinear(-40);
        $gateRange = $this->dbToLinear(-100);
        $compThreshold = $this->dbToLinear(-12);
        $compMakeup = $this->dbToLinear(0);
        $declick = sprintf('adeclick=w=55:o=75:a=%s', $adeclickAmplitude);
        $highpass24 = sprintf(
            'highpass=f=%d:poles=2,highpass=f=%d:poles=2',
            $highpassHz,
            $highpassHz,
        );

        return implode(',', [
            sprintf('afftdn=nr=%d:nf=%d:tn=1', $nrAmount, $afftdnNf),
            $declick,
            $highpass24,
            'highpass=f=80:poles=2',
            sprintf(
                'agate=threshold=%s:range=%s:attack=10:release=250',
                number_format($gateThreshold, 5, '.', ''),
                number_format($gateRange, 8, '.', ''),
            ),
            sprintf(
                'acompressor=threshold=%s:ratio=3.1:knee=5:attack=0.2:release=100:makeup=%s',
                number_format($compThreshold, 4, '.', ''),
                number_format($compMakeup, 4, '.', ''),
            ),
            sprintf('equalizer=f=5500:width_type=q:width=1.0:g=%d', $eqHarshCutDb),
            'highpass=f=5:poles=1',
            'loudnorm=I=-14:TP=-1:LRA=7',
            'volume=6.144dB',
            'alimiter=level_in=1:level_out=1:limit=1.0:attack=5:release=50:asc=1',
            $declick,
            $highpass24,
        ]);
    }

    private function dbToLinear(float $db): float
    {
        return 10 ** ($db / 20);
    }
}
