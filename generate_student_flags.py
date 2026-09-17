#!/usr/bin/env python3
"""
Instructor Tool: Student Flag Generator & Submission Verifier for Lab 02
Lab 02 Theme: Cryptographic Failures (A02) | Security Misconfiguration (A05) | Vulnerable Components (A06)
"""
import sys, hashlib, argparse

DEFAULT_SALT = "EHPT02_SECRET_SALT_2026"

def derive_flag(student_id: str, vuln_type: str, salt: str = DEFAULT_SALT) -> str:
    seed   = f"{student_id}_{vuln_type}_{salt}".encode('utf-8')
    digest = hashlib.sha256(seed).hexdigest()[:32]
    return f"FLAG{{{digest}}}"

def get_student_flags(student_id: str, salt: str = DEFAULT_SALT) -> dict:
    return {
        "WEAKHASH (A02)":   derive_flag(student_id, "WEAKHASH",  salt),
        "HARDCODED (A02)":  derive_flag(student_id, "HARDCODED", salt),
        "JWT (A02)":        derive_flag(student_id, "JWT",       salt),
        "MISCONFIG (A05)":  derive_flag(student_id, "MISCONFIG", salt),
        "VULNCOMP (A06)":   derive_flag(student_id, "VULNCOMP",  salt),
    }

def main():
    parser = argparse.ArgumentParser(description="Lab 02 Flag Generator & Anti-Cheat Verifier")
    parser.add_argument("student_id", nargs="?", help="Student ID")
    parser.add_argument("--salt",   default=DEFAULT_SALT)
    parser.add_argument("--verify", help="Flag string to verify")
    args = parser.parse_args()

    if args.verify and args.student_id:
        flags   = get_student_flags(args.student_id, args.salt)
        matched = [k for k, v in flags.items()
                   if v == args.verify or v.strip('FLAG{}') == args.verify.strip('FLAG{}')]
        if matched:
            print(f"[✅] MATCH! Student '{args.student_id}' owns flag: {matched[0]}")
            sys.exit(0)
        else:
            print(f"[❌] INVALID FLAG for student '{args.student_id}'. Plagiarism alert!")
            sys.exit(1)

    if not args.student_id:
        parser.print_help(); sys.exit(1)

    flags = get_student_flags(args.student_id, args.salt)
    print(f"{'='*62}")
    print(f"  Lab 02 Expected Flags — Student: {args.student_id}")
    print(f"  Salt: {args.salt}")
    print(f"{'='*62}")
    for vuln, flag in flags.items():
        print(f"  {vuln:<18}: {flag}")
    print(f"{'='*62}")

if __name__ == "__main__":
    main()
