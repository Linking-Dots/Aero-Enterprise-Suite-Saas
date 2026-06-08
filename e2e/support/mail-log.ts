import { execFileSync } from 'node:child_process';
import { join } from 'node:path';
import { ENV } from './env.ts';

export function getLatestLaravelLog(hostPath: string): string {
  const logsDir = join(hostPath, 'storage', 'logs');
  return execFileSync(
    'powershell',
    ['-Command', `Get-ChildItem "${logsDir}\\laravel*.log" | Sort-Object LastWriteTime -Descending | Select-Object -First 1 -ExpandProperty FullName`],
    { encoding: 'utf8' },
  ).trim();
}

export function extractVerificationCode(
  logPath: string,
  recipient: string,
  digits = 6,
  tailLines = 50,
): string {
  const log = execFileSync(
    'powershell',
    ['-Command', `Get-Content "${logPath}" -Tail ${tailLines} | Select-String "verification|code|${recipient}"`],
    { encoding: 'utf8' },
  );
  const match = log.match(/\b(\d{6})\b/);
  if (!match) {
    throw new Error(
      `No ${digits}-digit code found in log for ${recipient}. Log tail:\n${log}`,
    );
  }
  return match[1];
}

export async function waitForMailCode(
  recipient: string,
  hostPath: string,
  timeoutMs = 15_000,
): Promise<string> {
  const start = Date.now();
  while (Date.now() - start < timeoutMs) {
    try {
      const logPath = getLatestLaravelLog(hostPath);
      return extractVerificationCode(logPath, recipient);
    } catch {
      await new Promise(r => setTimeout(r, 500));
    }
  }
  throw new Error(
    `Timed out waiting for verification code for ${recipient} in ${hostPath}/storage/logs`,
  );
}
