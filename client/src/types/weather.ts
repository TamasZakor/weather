/**
 * Időjárási adatok típusdefiníciói
 */

export interface City {
  id: number;
  name: string;
  region: string;
}

export interface DateRange {
  minDate: string;
  maxDate: string;
}

export interface MetadataResponse {
  cities: City[];
  regions: string[];
  dateRange: DateRange;
}

export interface DailyWeatherRecord {
  date: string;
  minTemp: number;
  maxTemp: number;
  avgTemp: number;
  precipitation: number;
}

export interface ColumnStatistics {
  median: number | null;
  mode: number | null;
}
