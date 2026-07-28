import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { SeedOutlineParser } from '../../../application/services/seed-outline.parser';

const fixturesDir = join(__dirname, '../../fixtures');

describe('SeedOutlineParser', () => {
  const parser = new SeedOutlineParser();

  it('parses classroom Shape A sessions and Tema topics', () => {
    const md = readFileSync(
      join(fixturesDir, 'indice-curso-copilot.md'),
      'utf8',
    );
    const outline = parser.parse(md, 'classroom');
    expect(outline.sessions.length).toBeGreaterThanOrEqual(2);
    expect(outline.sessions[0]!.sessionNumber).toBe(1);
    expect(outline.sessions[0]!.title).toMatch(/Introducción/i);
    expect(outline.sessions[0]!.topics.length).toBeGreaterThanOrEqual(2);
    expect(outline.sessions[0]!.topics[0]!.title).toMatch(/GitHub Copilot/i);
  });

  it('parses video Shape B BLOQUE tables into topics', () => {
    const md = readFileSync(
      join(fixturesDir, 'pildoras_video_claude_usuarios.md'),
      'utf8',
    );
    const outline = parser.parse(md, 'video_pill');
    expect(outline.sessions.length).toBeGreaterThanOrEqual(2);
    expect(outline.sessions[0]!.sessionNumber).toBe(1);
    expect(outline.sessions[0]!.topics.length).toBeGreaterThanOrEqual(3);
  });

  it('throws on empty markdown', () => {
    expect(() => parser.parse('   ', 'classroom')).toThrow('EMPTY_MARKDOWN');
  });

  it('throws on unparseable markdown', () => {
    expect(() =>
      parser.parse('# Just a title\n\nNo sessions here.', 'classroom'),
    ).toThrow('UNPARSEABLE_MARKDOWN');
  });
});
