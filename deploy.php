<?php

namespace Deployer;

require 'recipe/common.php';

$hostname = 'root@13.237.16.95';
$webroot = '/var/www/html';

$git = trim(shell_exec('git config --get remote.origin.url'));
$dir = trim(shell_exec('basename -s .git `git config --get remote.origin.url`'));

// Project name
set('application', $dir);

// Webroot
set('webroot', $webroot);

// Project repository
set('repository', $git);

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// Writable dirs by web server
set('writable_use_sudo', false);
set('writable_mode', 'chmod');
set('writable_dirs', ['storage']);
set('allow_anonymous_stats', false);

// Hosts
host($hostname)
    ->set('deploy_path', '~/{{application}}')
    ->forwardAgent(true)
;

set('keep_releases', 1);
set('ssh_multiplexing', true);

// Tasks
task('deploy:webroot', function () {
    run('if [ -d ~/{{webroot}} -a ! -L ~/{{webroot}} ]; then mv ~/{{webroot}} ~/{{webroot}}_backup; fi');
    run('ln -sf {{deploy_path}}/current/web ~/{{webroot}}');
})->desc('Create Webroot Symlink');

task('app:pause', static function () {
    run('[ -d {{deploy_path}}/current ] && cd {{deploy_path}}/current && [ -f wait.php.dist ] && cp wait.php.dist wait.php || true');
})->desc('Pause application');

task('app:resume', static function () {
    run('[ -d {{deploy_path}}/current ] && cd {{deploy_path}}/current && [ -f wait.php ] && rm -f wait.php || true');
})->desc('Resume application (run vendor/bin/dep deploy:unlock)');


task('deploy:copy_dirs', function () {
    if (has('previous_release')) {
        foreach (get('copy_dirs') as $dir) {
            if (test("[ -d {{previous_release}}/$dir ]")) {
                // Delete directory if exists.
                run("if [ -d $(echo {{release_path}}/$dir) ]; then rm -rf {{release_path}}/$dir; fi");
                // Copy directory.
                run("if [ -d $(echo {{deploy_path}}/current/$dir) ]; then cp -rpf {{deploy_path}}/current/$dir {{release_path}}/$dir; fi");
            }
        }
    }
});

task('deploy:crontab', function () {
    run('echo "$(crontab < {{release_path}}/.crontab)"');
})->desc('Install Crontab');

task('release:notify', static function () {
    $release = run('cd {{deploy_path}}/current && git log -1 --pretty=short');
    $username = urlencode(run('whoami').'@'.run('hostname'));
    $attachment = urlencode(json_encode([
        0 => [
            'fallback' => $release,
            'text' => $release,
            'color' => 'good',
        ],
    ]));
    run("curl --silent 'https://slack.com/api/chat.postMessage?token=xoxp-2652323063-2652323065-2668320950-38cfda&channel=%23h6-deployments&attachments=$attachment&username=$username&pretty=1'");
})->desc('Send Slack Notification');

task('release:notify_failure', static function () {
    $release = run('cd {{deploy_path}}/current && git log -1 --pretty=short');
    $username = urlencode(run('whoami').'@'.run('hostname'));
    $attachment = urlencode(json_encode([
        0 => [
            'fallback' => 'Deploy Failed: '.$release,
            'text' => '*Deploy Failed:* '.$release,
            'color' => 'danger',
        ],
    ]));
    run("curl --silent 'https://slack.com/api/chat.postMessage?token=xoxp-2652323063-2652323065-2668320950-38cfda&channel=%23h6-deployments&attachments=$attachment&username=$username&pretty=1'");
})->desc('Send Slack Failure Notification');
desc('Deploy your project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:copy_dirs',
    'deploy:writable',
    'deploy:vendors',
    'deploy:clear_paths',
    'deploy:symlink',
    'app:pause',
    'deploy:webroot',
    'deploy:crontab',
    'app:resume',
    'deploy:unlock',
    'cleanup',
    'success',
]);

// [Optional] If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');
after('deploy:failed', 'release:notify_failure');
after('deploy', 'release:notify');
