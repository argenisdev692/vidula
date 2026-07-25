import { Injectable } from '@nestjs/common';
import PDFDocument from 'pdfkit';

/**
 * Renders the Gemini script-review (markdown) into a downloadable PDF.
 * Lightweight markdown handling: `##` headings, `|` table rows (monospace),
 * `-`/`*` bullets, everything else as paragraphs. Table separator rows
 * (`|---|`) are skipped.
 */
@Injectable()
export class ReviewPdfService {
  build(input: {
    jobUuid: string;
    reviewMarkdown: string;
    generatedAt: Date;
  }): Promise<Buffer> {
    return new Promise<Buffer>((resolve, reject) => {
      const doc = new PDFDocument({ size: 'A4', margin: 48 });
      const chunks: Buffer[] = [];
      doc.on('data', (chunk: Buffer) => chunks.push(chunk));
      doc.on('end', () => resolve(Buffer.concat(chunks)));
      doc.on('error', reject);

      doc
        .fontSize(20)
        .font('Helvetica-Bold')
        .text('Video Cleaning — Script Review', { align: 'center' });
      doc.moveDown(0.3);
      doc
        .fontSize(9)
        .font('Helvetica')
        .fillColor('#64748b')
        .text(`Job: ${input.jobUuid}  ·  ${input.generatedAt.toISOString()}`, {
          align: 'center',
        })
        .fillColor('#000');
      doc.moveDown(0.6);
      doc.moveTo(48, doc.y).lineTo(547, doc.y).strokeColor('#e2e8f0').stroke();
      doc.moveDown(0.6);

      for (const raw of input.reviewMarkdown.split('\n')) {
        const line = raw.trimEnd();
        if (this.isTableSeparator(line)) continue;

        if (line.startsWith('## ')) {
          doc.moveDown(0.4);
          doc.fontSize(13).font('Helvetica-Bold').text(line.slice(3));
          doc.moveDown(0.2);
        } else if (line.startsWith('# ')) {
          doc.moveDown(0.4);
          doc.fontSize(15).font('Helvetica-Bold').text(line.slice(2));
          doc.moveDown(0.2);
        } else if (line.startsWith('|')) {
          doc.fontSize(9).font('Courier').text(line);
        } else if (line.startsWith('- ') || line.startsWith('* ')) {
          doc
            .fontSize(10)
            .font('Helvetica')
            .text(`• ${line.slice(2)}`, {
              indent: 10,
            });
        } else if (line.length === 0) {
          doc.moveDown(0.3);
        } else {
          doc.fontSize(10).font('Helvetica').text(line);
        }
      }

      doc.end();
    });
  }

  private isTableSeparator(line: string): boolean {
    return /^\|?[\s|:-]+\|?$/.test(line) && line.includes('-');
  }
}
