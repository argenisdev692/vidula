import { Test } from '@nestjs/testing';
import { LoggerService } from '../../../logger/logger.service';
import { CutPlannerService } from '../pipeline/cut-planner.service';
import { FillerService } from '../pipeline/filler.service';
import type { WordTimestamp } from '../video-export.types';

const logger = {
  setContext: jest.fn(),
  info: jest.fn(),
  warn: jest.fn(),
  error: jest.fn(),
  debug: jest.fn(),
};

describe('CutPlannerService.invertCuts', () => {
  const planner = new CutPlannerService();

  it('inverts a single mid cut into two keep ranges', () => {
    const keep = planner.invertCuts([{ start: 10, end: 12 }], 20);
    expect(keep).toEqual([
      { start: 0, end: 10 },
      { start: 12, end: 20 },
    ]);
    expect(planner.totalDuration(keep)).toBe(18);
  });

  it('merges overlapping cuts and drops sub-min fragments', () => {
    const keep = planner.invertCuts(
      [
        { start: 5, end: 9 },
        { start: 8, end: 12 },
      ],
      20,
    );
    expect(keep).toEqual([
      { start: 0, end: 5 },
      { start: 12, end: 20 },
    ]);
  });

  it('falls back to the full clip when everything is cut', () => {
    expect(planner.invertCuts([{ start: 0, end: 20 }], 20)).toEqual([
      { start: 0, end: 20 },
    ]);
  });
});

describe('FillerService', () => {
  let filler: FillerService;

  beforeEach(async () => {
    const moduleRef = await Test.createTestingModule({
      providers: [FillerService, { provide: LoggerService, useValue: logger }],
    }).compile();
    filler = moduleRef.get(FillerService);
  });

  const word = (text: string, start: number, end: number): WordTimestamp => ({
    text,
    start,
    end,
  });

  it('cuts filler words including accents and regex variants', () => {
    const words = [
      word('Hola', 0, 0.5),
      word('eh', 0.6, 0.8),
      word('mundo', 1.0, 1.5),
      word('ehhh', 2.0, 2.3),
    ];
    const cuts = filler.findFillerCuts(words);
    // "eh" and "ehhh" both match (the latter via /^e+h+$/).
    expect(cuts).toHaveLength(2);
  });

  it('removes a stutter run keeping the last occurrence', () => {
    const words = [
      word('y', 0, 0.2),
      word('y', 0.3, 0.5),
      word('y', 0.6, 0.8),
      word('vamos', 1.0, 1.5),
    ];
    const cuts = filler.findStutterCuts(words);
    // Two of the three "y" are cut; the run keeps the last.
    expect(cuts).toHaveLength(2);
  });

  it('detects PAUSA and backtracks across the tight bad take', () => {
    const words = [
      word('bueno', 0, 0.4),
      word('esto', 0.5, 0.9), // tight gaps → part of the bad take
      word('pausa', 1.0, 1.4),
      word('ahora', 3.0, 3.4), // big gap after keyword
    ];
    const cuts = filler.findPauseCuts(words);
    expect(cuts).toHaveLength(1);
    // Cut ends at the keyword end, starts somewhere before it.
    expect(cuts[0].end).toBeCloseTo(1.4, 3);
    expect(cuts[0].start).toBeLessThan(1.0);
  });

  it('matches multi-word "pausa aca" over the bare "pausa"', () => {
    const words = [
      word('listo', 0, 0.4),
      word('pausa', 0.5, 0.9),
      word('aca', 1.0, 1.4),
    ];
    const cuts = filler.findPauseCuts(words);
    expect(cuts).toHaveLength(1);
    expect(cuts[0].end).toBeCloseTo(1.4, 3);
  });
});
