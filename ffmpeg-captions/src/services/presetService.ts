import fs from 'fs';
import path from 'path';
import { CaptionPreset, CaptionStyle } from '../types';
import logger from '../utils/logger';

const PRESETS_DIR = path.join(__dirname, '../../presets');

export class PresetService {
  private presets: Map<string, CaptionPreset> = new Map();

  constructor() {
    this.loadPresets();
  }

  private loadPresets(): void {
    try {
      if (!fs.existsSync(PRESETS_DIR)) {
        logger.warn(`Presets directory not found: ${PRESETS_DIR}`);
        return;
      }

      const files = fs.readdirSync(PRESETS_DIR);
      
      for (const file of files) {
        if (path.extname(file) === '.json') {
          try {
            const filePath = path.join(PRESETS_DIR, file);
            const content = fs.readFileSync(filePath, 'utf-8');
            const preset: CaptionPreset = JSON.parse(content);
            
            // Validate preset structure
            if (this.validatePreset(preset)) {
              this.presets.set(preset.name, preset);
              logger.info(`Loaded preset: ${preset.name}`);
            } else {
              logger.warn(`Invalid preset format in file: ${file}`);
            }
          } catch (error) {
            logger.error(`Error loading preset file ${file}:`, error);
          }
        }
      }
    } catch (error) {
      logger.error('Error loading presets:', error);
    }
  }

  private validatePreset(preset: any): preset is CaptionPreset {
    return (
      preset &&
      typeof preset.name === 'string' &&
      typeof preset.displayName === 'string' &&
      typeof preset.description === 'string' &&
      preset.defaults &&
      Array.isArray(preset.customizable)
    );
  }

  getAllPresets(): CaptionPreset[] {
    return Array.from(this.presets.values());
  }

  getPreset(name: string): CaptionPreset | null {
    return this.presets.get(name) || null;
  }

  getPresetStyle(name: string, customizations?: Partial<CaptionStyle>): CaptionStyle | null {
    const preset = this.getPreset(name);
    if (!preset) {
      return null;
    }

    // Merge preset defaults with customizations
    return {
      ...preset.defaults,
      ...customizations,
    };
  }

  validateCustomizations(presetName: string, customizations: Partial<CaptionStyle>): { isValid: boolean; errors: string[] } {
    const preset = this.getPreset(presetName);
    if (!preset) {
      return { isValid: false, errors: [`Preset '${presetName}' not found`] };
    }

    const errors: string[] = [];
    const customizableKeys = preset.customizable.map(c => c.key);

    for (const [key, value] of Object.entries(customizations)) {
      if (!customizableKeys.includes(key as keyof CaptionStyle)) {
        errors.push(`Parameter '${key}' is not customizable for preset '${presetName}'`);
        continue;
      }

      const param = preset.customizable.find(c => c.key === key);
      if (!param) continue;

      // Validate based on type
      switch (param.type) {
        case 'number':
          if (typeof value !== 'number') {
            errors.push(`Parameter '${key}' must be a number`);
          } else if (param.min !== undefined && value < param.min) {
            errors.push(`Parameter '${key}' must be at least ${param.min}`);
          } else if (param.max !== undefined && value > param.max) {
            errors.push(`Parameter '${key}' must be at most ${param.max}`);
          }
          break;
        case 'color':
          if (typeof value !== 'string' || !/^[0-9A-Fa-f]{6}$/.test(value)) {
            errors.push(`Parameter '${key}' must be a valid hex color (6 characters, no # prefix)`);
          }
          break;
        case 'select':
          if (param.options && !param.options.includes(value as string)) {
            errors.push(`Parameter '${key}' must be one of: ${param.options.join(', ')}`);
          }
          break;
        case 'boolean':
          if (typeof value !== 'boolean') {
            errors.push(`Parameter '${key}' must be a boolean`);
          }
          break;
        case 'font':
          if (typeof value !== 'string') {
            errors.push(`Parameter '${key}' must be a string`);
          }
          // Additional font validation could be added here
          break;
      }
    }

    return { isValid: errors.length === 0, errors };
  }
}

export const presetService = new PresetService();
