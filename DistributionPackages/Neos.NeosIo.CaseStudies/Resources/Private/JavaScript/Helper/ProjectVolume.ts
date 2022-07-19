
export const PROJECT_VOLUME_MAP: Record<number, string> = {
    1: '',
    5: '< 100 h',
    10: '100 - 499h',
    15: '500 - 999h',
    20: '1000 - 3000h',
    25: '> 3000h'
};

export default function getProjectVolume(projectVolume: number): string {
    return PROJECT_VOLUME_MAP[projectVolume] ?? '';
}
