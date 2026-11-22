export function parseGazettePath(pathname) {
  const match = String(pathname || '').match(/^\/sayi\/([1-9]\d*(?:\.[1-9]\d*)?)\/yaprak\/([1-9]\d*)$/);
  return match ? { issue: match[1], sheet: Number(match[2]) } : null;
}

export function gazettePath(issue, sheet) {
  return `/sayi/${issue}/yaprak/${sheet}`;
}

export function findGazetteIssue(archive, issueNumber) {
  return archive.find((issue) => {
    const number = issue.is_extra && issue.extra_sequence
      ? `${issue.issue_number}.${issue.extra_sequence}`
      : String(issue.issue_number);
    return number === String(issueNumber);
  });
}
