module.exports = async ({github, context}) => {

  console.log('================================');
  console.log('============== github ===============');
  console.log('================================');
  console.log(github);
  console.log('================================');
  console.log('============= context =============');
  console.log('================================');
  console.log(context);
  console.log('================================');
  console.log('============= payload ================');
  console.log('================================');
  console.log(context.payload);
  console.log('================================');
  console.log('================================');
  console.log('================================');

  console.log('============== owner =============');
  console.log(context.repo.owner);
  console.log('================================');
  console.log('============== context.repo =============');
  console.log(context.repo.owner);
  console.log('================================');
  console.log('================================');

  return;

  const issues = await github.rest.pulls.list({
    owner: context.repo.owner,
    repo: context.repo.repo,
    state: 'open',
    head: `${context.repo.owner}:${context.ref.replace('refs/heads/', '')}`
  })

  const pr = context.issue.number || issues.data[0].number;

  const comments = await github.rest.issues.listComments({
    owner: context.repo.owner,
    repo: context.repo.repo,
    issue_number: pr
  })

  const composerLockDiffComments = comments.data.filter(comment => {
    return comment.body.indexOf("#composer-lock-diff-comment") != -1
  })

  composerLockDiffComments.map(function (comment) {
    github.rest.issues.deleteComment({
      owner: context.repo.owner,
      repo: context.repo.repo,
      comment_id: comment.id
    })
  })

  // https://stackoverflow.com/a/52575123/1038565
  const execSync = require('child_process').execSync;

  execSync('composer global require davidrjonas/composer-lock-diff:^1.0');
  execSync(`git fetch origin ${context.repo.default_branch}`);

  const output = execSync('~/.composer/vendor/bin/composer-lock-diff --from=origin/main --md', { encoding: 'utf-8' });

  if (!output) {
    return
  }

  github.rest.issues.createComment({
    issue_number: pr,
    owner: context.repo.owner,
    repo: context.repo.repo,
    body: output + "\n<!-- #composer-lock-diff-comment -->"
  })
}
