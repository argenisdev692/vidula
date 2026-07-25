import { Injectable } from '@nestjs/common';

/**
 * Builds the ffmpeg audio DSP chain for noise removal + studio sound. This is
 * Layer B (audio quality) — it does NOT use Whisper. Ported 1:1 from the
 * reference module's `cleaner.py` (`_build_filter_chain`).
 *
 * The chain is returned as a comma-joined filter string and injected by
 * `FfmpegService.render` onto the final concatenated audio, so enhancement
 * happens in the same single render pass.
 */
@Injectable()
export class AudioEnhanceService {
  /** Mastering chain tuned for spoken-voice tutorials (dB values from cleaner.py). */
  private static readonly CFG = {
    nrAmount: 12,
    afftdnNf: -25,
    adeclickAmplitude: 2.0,
    highpassHz: 120,
    eqHarshCutDb: -2,
    gateThresholdDb: -40,
    gateRangeDb: -100,
    gateAttackMs: 10,
    gateReleaseMs: 250,
    compThresholdDb: -12,
    compRatio: 3.1,
    compKneeDb: 5,
    compAttackMs: 0.2,
    compReleaseMs: 100,
    compMakeupDb: 0,
    targetLufs: -14,
    truePeak: -1,
    lra: 7,
    amplifyDb: 6.144,
  } as const;

  buildChain(): string {
    const c = AudioEnhanceService.CFG;
    const gateThreshold = AudioEnhanceService.dbToLinear(c.gateThresholdDb);
    const gateRange = AudioEnhanceService.dbToLinear(c.gateRangeDb);
    const compThreshold = AudioEnhanceService.dbToLinear(c.compThresholdDb);
    const compMakeup = AudioEnhanceService.dbToLinear(c.compMakeupDb);

    const declick = `adeclick=w=55:o=75:a=${c.adeclickAmplitude}`;
    // 24 dB/oct = two cascaded 2-pole stages (ffmpeg caps poles at 2).
    const highpass24 = `highpass=f=${c.highpassHz}:poles=2,highpass=f=${c.highpassHz}:poles=2`;

    return [
      // 1. Noise reduction (afftdn, track noise).
      `afftdn=nr=${c.nrAmount}:nf=${c.afftdnNf}:tn=1`,
      // 2. Click removal.
      declick,
      // 3. High-pass 120 Hz, 24 dB/oct (kills motor/car rumble).
      highpass24,
      // 4. Gentle low-end roll-off.
      'highpass=f=80:poles=2',
      // 5. Noise gate.
      `agate=threshold=${gateThreshold.toFixed(5)}:range=${gateRange.toFixed(8)}:attack=${c.gateAttackMs}:release=${c.gateReleaseMs}`,
      // 6. Compressor.
      `acompressor=threshold=${compThreshold.toFixed(4)}:ratio=${c.compRatio}:knee=${c.compKneeDb}:attack=${c.compAttackMs}:release=${c.compReleaseMs}:makeup=${compMakeup.toFixed(4)}`,
      // 6b. Harsh-cut EQ at 5.5 kHz (tames brightness).
      `equalizer=f=5500:width_type=q:width=1.0:g=${c.eqHarshCutDb}`,
      // 7. DC removal then loudness normalization.
      'highpass=f=5:poles=1',
      `loudnorm=I=${c.targetLufs}:TP=${c.truePeak}:LRA=${c.lra}`,
      // 8. Amplify with a brick-wall limiter (no clipping).
      `volume=${c.amplifyDb}dB`,
      'alimiter=level_in=1:level_out=1:limit=1.0:attack=5:release=50:asc=1',
      // 9 & 10. Second de-click + anti-keyboard high-pass pass.
      declick,
      highpass24,
    ].join(',');
  }

  private static dbToLinear(db: number): number {
    return Math.pow(10, db / 20);
  }
}
