import { Injectable } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { GoogleGenAI } from '@google/genai';
import type { IPolicy } from 'cockatiel';
import { LoggerService } from '../../../logger/logger.service';
import { createExternalServicePolicy } from '../../../shared/external/resilience';
import type { WordTimestamp } from '../video-export.types';

/**
 * Senior-editor review of the cleaned video against the user's script, powered
 * by Gemini (same workflow as Google AI Studio: upload the PDF/markdown script
 * and get an edit report). Based on `docs/prompt-review-video.md`.
 *
 * Gemini reads the PDF natively (sent as inline bytes); markdown is sent as
 * text. We do NOT send the video — Gemini works from the post-cleaning
 * transcript (with original-recording timestamps) plus the cut diagnostics.
 */
const REVIEW_SYSTEM_PROMPT = `Actúa como un editor de video senior. El usuario grabó un tutorial y ya se le aplicó una limpieza automática (merge, corte de silencios y, opcionalmente, muletillas/tartamudeos y tomas marcadas con "PAUSA"). Recibes:
1. El GUION original (PDF o markdown adjunto).
2. La TRANSCRIPCIÓN del video YA limpio, con marcas de tiempo [mm:ss] que corresponden a la grabación original.
3. Un resumen de los CORTES ya aplicados.

REGLAS:
- "PAUSA": si en la transcripción final aún aparece la palabra "pausa"/"pausa acá", márcalo como fragmento que quedó sin cortar y debe eliminarse (con su tiempo).
- No es teleprompter: no exijas las palabras exactas del guion; evalúa si el concepto quedó claro y directo. Si el usuario "da vueltas", indica dónde recortar.
- Auditoría de temas: confirma si se cubren todos los temas del guion con sus minutos.
- Audio: señala muletillas o repeticiones que sigan presentes y silencios largos.
- Plan de recorte: si el video sigue largo, indica qué demostraciones son redundantes y se pueden acortar.

ENTREGA EXACTAMENTE este formato markdown:
## Resumen
(2-3 frases: ¿se cumplió el guion? estado general)

## Tabla de trabajo
| Tiempo (Inicio - Fin) | Acción | Motivo / Observación |
|---|---|---|
(filas con CORTAR / REDUCIR y el porqué; si no hay nada que cortar, una fila que lo diga)

## Fragmentos "PAUSA" residuales
(lista de tiempos donde aún se oye "pausa", o "Ninguno")

## Conclusión
(¿se cumplieron los objetivos de aprendizaje? ¿cuánto tiempo se ahorró aprox.?)`;

export interface ScriptReviewInput {
  scriptText?: string;
  scriptPdfBase64?: string;
  words: WordTimestamp[];
  diagnostics: {
    originalDurationSeconds: number;
    finalDurationSeconds: number;
    silenceCuts: number;
    fillerCuts: number;
    stutterCuts: number;
    pauseCuts: number;
    leftoverPauseFragments: number;
  };
}

const REVIEW_TIMEOUT_MS = 120_000;

@Injectable()
export class ScriptReviewService {
  private client: GoogleGenAI | null = null;
  private readonly model: string;
  // Retry + circuit breaker (cockatiel 'ai' profile) — a Gemini outage trips the
  // breaker instead of retrying every job; the review is best-effort anyway.
  private readonly policy: IPolicy = createExternalServicePolicy(
    'gemini-script-review',
    'ai',
  );

  constructor(
    private readonly config: ConfigService,
    private readonly logger: LoggerService,
  ) {
    this.logger.setContext(ScriptReviewService.name);
    this.model = this.config.get<string>(
      'GEMINI_TEXT_MODEL',
      'gemini-2.5-flash',
    );
  }

  async review(input: ScriptReviewInput): Promise<string> {
    const parts: Array<
      { text: string } | { inlineData: { data: string; mimeType: string } }
    > = [{ text: this.buildUserText(input) }];

    if (input.scriptPdfBase64) {
      parts.push({
        inlineData: {
          data: input.scriptPdfBase64,
          mimeType: 'application/pdf',
        },
      });
    }

    // Breaker + retry on the outside, per-attempt timeout on the inside.
    const response = await this.policy.execute(() =>
      this.withTimeout(
        this.getClient().models.generateContent({
          model: this.model,
          contents: [{ role: 'user', parts }],
          config: {
            systemInstruction: REVIEW_SYSTEM_PROMPT,
            temperature: 0.3,
            maxOutputTokens: 4096,
            responseMimeType: 'text/plain',
          },
        }),
      ),
    );

    const text = response.text ?? '';
    this.logger.info('ScriptReviewService.review', {
      model: this.model,
      chars: text.length,
    });
    return text.trim();
  }

  private buildUserText(input: ScriptReviewInput): string {
    const d = input.diagnostics;
    const transcript = ScriptReviewService.formatTranscript(input.words);
    const scriptBlock = input.scriptText
      ? `\n\n=== GUION (markdown) ===\n${input.scriptText}`
      : '\n\n(El guion va adjunto como PDF.)';

    return (
      `=== CORTES YA APLICADOS ===\n` +
      `Duración original: ${ScriptReviewService.mmss(d.originalDurationSeconds)} · ` +
      `Duración final: ${ScriptReviewService.mmss(d.finalDurationSeconds)}\n` +
      `Silencios cortados: ${d.silenceCuts} · Muletillas: ${d.fillerCuts} · ` +
      `Tartamudeos: ${d.stutterCuts} · PAUSA: ${d.pauseCuts} · ` +
      `PAUSA residuales detectadas: ${d.leftoverPauseFragments}\n\n` +
      `=== TRANSCRIPCIÓN DEL VIDEO LIMPIO ([mm:ss] = grabación original) ===\n` +
      `${transcript}` +
      scriptBlock
    );
  }

  /** Group words into timestamped lines so Gemini can produce a time-coded table. */
  private static formatTranscript(words: WordTimestamp[]): string {
    if (words.length === 0) return '(transcripción vacía)';
    const lines: string[] = [];
    let buffer: string[] = [];
    let lineStart = words[0].start;
    for (const word of words) {
      if (buffer.length === 0) lineStart = word.start;
      buffer.push(word.text.trim());
      if (buffer.length >= 14) {
        lines.push(
          `[${ScriptReviewService.mmss(lineStart)}] ${buffer.join(' ')}`,
        );
        buffer = [];
      }
    }
    if (buffer.length > 0) {
      lines.push(
        `[${ScriptReviewService.mmss(lineStart)}] ${buffer.join(' ')}`,
      );
    }
    return lines.join('\n');
  }

  private static mmss(seconds: number): string {
    const total = Math.max(0, Math.floor(seconds));
    const m = Math.floor(total / 60);
    const s = total % 60;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
  }

  private async withTimeout<T>(promise: Promise<T>): Promise<T> {
    let timer: NodeJS.Timeout;
    const timeout = new Promise<never>((_, reject) => {
      timer = setTimeout(
        () => reject(new Error('Gemini review timed out')),
        REVIEW_TIMEOUT_MS,
      );
    });
    try {
      return await Promise.race([promise, timeout]);
    } finally {
      clearTimeout(timer!);
    }
  }

  private getClient(): GoogleGenAI {
    if (!this.client) {
      this.client = new GoogleGenAI({
        apiKey: this.config.getOrThrow<string>('GEMINI_API_KEY'),
      });
    }
    return this.client;
  }
}
