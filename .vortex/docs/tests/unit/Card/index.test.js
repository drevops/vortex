import * as CardIndex from '../../../src/components/Card/index';
import { Card, CardGrid } from '../../../src/components/Card';
import CardComponent from '../../../src/components/Card/Card';
import CardGridComponent from '../../../src/components/Card/CardGrid';

describe('Card Index Exports', () => {
  test('exports Card component correctly', () => {
    expect(Card).toBeDefined();
    expect(Card).toBe(CardComponent);
    expect(typeof Card).toBe('function');
    expect(CardIndex.Card).toBe(CardComponent);
  });

  test('exports CardGrid component correctly', () => {
    expect(CardGrid).toBeDefined();
    expect(CardGrid).toBe(CardGridComponent);
    expect(typeof CardGrid).toBe('function');
    expect(CardIndex.CardGrid).toBe(CardGridComponent);
  });

  test('exports both components from index', () => {
    expect(Card).not.toBe(CardGrid);
    expect(Card).toBeDefined();
    expect(CardGrid).toBeDefined();
    expect(CardIndex.Card).toBeDefined();
    expect(CardIndex.CardGrid).toBeDefined();
  });

  test('index file exports match named imports', () => {
    expect(CardIndex.Card).toBe(Card);
    expect(CardIndex.CardGrid).toBe(CardGrid);
  });
});
