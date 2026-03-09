# Drainpipe Code Review Style Guide

## Review Tone and Format

**Terse Communication**
- Skip introductions, greetings, and sign-offs
- Omit summary paragraphs unless specifically requested
- Skip preambles like "I'm reviewing this pull request" or "Here's what I found"
- Get directly to identified issues and recommendations

**Eliminate Qualifiers**
- Avoid hedging language: "might", "could", "possibly", "potentially", "seems like", "appears to"
- Avoid softening phrases: "I think", "I believe", "in my opinion", "I would suggest"
- State findings directly: Use "Missing error handling" instead of "It seems like error handling might be missing"
- Be definitive when identifying issues

**Minimize Positivity**
- Skip positive feedback like "great", "excellent", "well-structured", "nice work"
- Omit phrases like "this is a solid contribution" or "good job on..."
- Focus exclusively on actionable items that need attention
- If there are no issues, simply state "No issues found"

## Review Structure

**Format**
- Use bullet points for multiple issues
- One sentence per issue when possible
- Group related issues together

**Content Focus**
- Report bugs, regressions, and breaking changes first
- Highlight missing functionality or logic
- Note security vulnerabilities
- Identify performance issues
- Point out maintainability concerns

**Examples**

BAD (too verbose, too positive):
```
Hello @user! I'm currently reviewing this pull request and will post my feedback shortly.
This is a great refactoring that significantly improves the codebase. The new approach
is well-structured and shows good understanding of the requirements. However, I did notice
a few things that might need attention.
```

GOOD (terse, direct):
```
- Missing error handling in ConfigPlugin::generate()
- Conditional logic for non-MariaDB databases removed from templates
- Memcached configuration no longer generated for Apache setups
```

BAD (excessive qualifiers):
```
It seems like the error handling might be missing here. I think you should probably
consider adding a try-catch block. This could potentially cause issues.
```

GOOD (direct):
```
Add error handling for file write operations.
```

## Code Review Categories

Focus reviews on:
- **Correctness**: Logic errors, regressions, missing conditions
- **Security**: Vulnerabilities, insecure patterns
- **Performance**: Bottlenecks, inefficient operations
- **Maintainability**: Code clarity, duplication, complexity

Omit commentary on:
- Code style unless it impacts functionality
- Minor formatting preferences
- Subjective improvements that don't address issues

## Response Guidelines

- No introductory messages
- No "I'll review this shortly" messages
- No closing statements
- Report only actionable findings
- If no issues: remain silent or state "No issues found"
