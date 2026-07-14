#!/bin/bash
# Smoke driver for the Ahlan wa Sahlan restaurant POS (Laravel + Filament).
# Starts `php artisan serve` if nothing is on the port, then asserts every
# public HTTP surface. Exits non-zero on any failure.
#
# Usage:  .claude/skills/run-ahlanwasahsalan/smoke.sh        (from repo root)
#         PORT=8080 .claude/skills/run-ahlanwasahsalan/smoke.sh
set -u
PORT="${PORT:-8000}"
BASE="http://localhost:$PORT"
cd "$(dirname "$0")/../../.."

fail=0
check() { # label url expected-status [grep-pattern]
    local label=$1 url=$2 want=$3 pat=${4:-}
    local body code
    body=$(curl -s -m 15 -w $'\n%{http_code}' "$url") || { echo "FAIL $label (curl error)"; fail=1; return; }
    code=${body##*$'\n'}
    body=${body%$'\n'*}
    if [ "$code" != "$want" ]; then echo "FAIL $label (got $code, want $want)"; fail=1; return; fi
    if [ -n "$pat" ] && ! grep -q "$pat" <<<"$body"; then echo "FAIL $label (200 but missing '$pat')"; fail=1; return; fi
    echo "OK   $label"
}

# 1. PHP itself must run — Herd auto-updates can replace it with a binary
#    built for a newer macOS (dyld libc++ abort). See SKILL.md gotcha #1.
if ! php -v >/dev/null 2>&1; then
    echo "FAIL php binary is broken (dyld/libc++ abort? see SKILL.md gotcha #1)"
    exit 1
fi

# 2. MySQL must be up (XAMPP's mysqld serves 127.0.0.1:3306 on this machine).
if ! php artisan tinker --execute='DB::select("select 1");' >/dev/null 2>&1; then
    echo "FAIL database unreachable — start MySQL (XAMPP) first"
    exit 1
fi

# 3. Server: reuse a running one, else start our own in the background.
if ! curl -s -o /dev/null -m 2 "$BASE"; then
    (php artisan serve --port="$PORT" > /tmp/ahlan-serve.log 2>&1 &)
    sleep 3
fi

TOKEN=$(php artisan tinker --execute='echo \App\Models\DiningTable::where("is_active",true)->value("qr_token");' 2>/dev/null | tail -1)
IMG=$(ls storage/app/public/products 2>/dev/null | head -1)

check "customer landing page /" "$BASE/" 200 "Staff login"
check "admin login page" "$BASE/admin/login" 200 "Username"
if [ -n "$TOKEN" ]; then
    check "public QR menu /order/$TOKEN" "$BASE/order/$TOKEN" 200 "cm-item"
else
    echo "SKIP public QR menu (no active dining tables — owner adds them under Tables & QR Codes)"
fi
if [ -n "$IMG" ]; then
    check "food image via storage symlink" "$BASE/storage/products/$IMG" 200
else
    echo "SKIP food image (no product photos uploaded yet)"
fi

[ $fail -eq 0 ] && echo "SMOKE PASSED — app is serving at $BASE" || echo "SMOKE FAILED"
exit $fail
