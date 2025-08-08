"use strict";

const gulp 				= require('gulp');
const fs 					= require('fs');
const path 				= require('path');
const { spawn } 	= require('child_process');
const { exec } 		= require('child_process');
const browserSync = require('browser-sync').create();
const domain			= 'wptailwindcss.local';


/**
 * Global Paths
 ***********************************************************************************************************************/
const basePaths = {
	php: [ './**/*.php','./*.php', '!./functions.php' ],
	css: {
		input: './assets/styles/src/input.css',
		output: './assets/styles/css/output.css'
	}
};


/**
 * BrowserSync
 ***********************************************************************************************************************/
const browserSyncOptions = {
	logPrefix: domain.toUpperCase(),
	proxy: "https://" + domain,
	host: domain,
	open: false,
	notify: false,
	ghost: false,
	ui: false,
	files: [
		'./**/*.php',
		'./**/*.html',
		'./**/*.js',
		'!./functions.php',
		'!./assets/styles/css/output.css'
	],
	injectChanges: true,
	snippetOptions: {
		whitelist: [ "/wp-admin/admin-ajax.php" ],
		blacklist: [ "/wp-admin/**" ],
		ignorePaths: [ "wp-admin/**" ]
	},
	https: {
		key: "/Applications/MAMP/Library/OpenSSL/certs/" + domain + ".key",
		cert: "/Applications/MAMP/Library/OpenSSL/certs/" + domain + ".crt"
	}
};


/**
 * Setup Prettier
 ***********************************************************************************************************************/
gulp.task('init-prettier', function (done) {
const prettierIgnorePath 		= path.resolve(__dirname, '.prettierignore');
const prettierRcPath 				= path.resolve(__dirname, '.prettierrc');
const prettierIgnoreContent = `# Ignore local node_modules (just in case)
node_modules/

# Ignore build output
assets/styles/css/

# Ignore WordPress system files (relative path from theme)
../../../wp-admin/
../../../wp-includes/
../../../wp-tailwind/

# Optional: ignore WordPress plugins and other themes
../../plugins/

# Optional: Ignore vendor folders, backups, logs
vendor/
*.log
*.zip
.DS_Store
`;
	
	const prettierRcContent = {
		"plugins": [
			"prettier-plugin-css-order",
			"prettier-plugin-tailwindcss",
			"prettier-plugin-organize-attributes",
			"prettier-plugin-classnames",
			"prettier-plugin-merge"
		],
		"tailwindStylesheet": "./assets/styles/src/input.css",
		"tailwindPreserveWhitespace": true,
		"cssDeclarationSorterOrder" : "smacss",
		"cssDeclarationSorterKeepOverrides" : true,
		"printWidth": 120,
		"tabWidth": 2,
		"semi": true,
		"trailingComma": "es5",
		"useTabs": false,
		"bracketSpacing": true,
		"bracketSameLine": true,
		"endingPosition": "relative",
		"attributeGroups": [
			"^id$",
			"^x-",
			"^:",
			"^class$",
			"^name$",
			"$DEFAULT",
			"^aria-"
		]
	};
	
	if ( !fs.existsSync(prettierIgnorePath) ) {
		fs.writeFileSync(prettierIgnorePath, prettierIgnoreContent);
		console.log('SUCCESS! .prettierignore file created.');
	} else {
		console.log('SKIPPED: .prettierignore already exists!');
	}
	
	if ( !fs.existsSync(prettierRcPath) ) {
		fs.writeFileSync(prettierRcPath, JSON.stringify(prettierRcContent, null, 2));
		console.log('SUCCESS! .prettierrc file created.');
	} else {
		console.log('SKIPPED: .prettierrc already exists!');
	}
	
	done();
});


/**
 * Setup Tailwind Environment
 ***********************************************************************************************************************/
gulp.task('init-wp-tailwind', function (done) {
	const tailwindDir  				= path.resolve(__dirname, './../../../wp-tailwind');
	const packageJsonPath			= path.join(tailwindDir, 'package.json');
	const packageJsonContent 	= {
		name: "tailwind-css",
		version: "1.0.0",
		main: "index.js",
		scripts: {},
		keywords: [],
		author: "Denis",
		license: "ISC",
		description: "",
		devDependencies: {
			"tailwindcss": "^4.1.11",
			"tailwind-clamp": "^4.1.0",
			"@tailwindcss/aspect-ratio": "^0.4.2",
			"@tailwindcss/cli": "^4.0.9",
			"@tailwindcss/container-queries": "^0.1.1",
			"@tailwindcss/forms": "^0.5.10",
			"@tailwindcss/line-clamp": "^0.4.4",
			"@tailwindcss/typography": "^0.5.16",
			"@types/tailwindcss": "^3.0.11"
		}
	};
	
	// 1. Create the directory if it doesn't exist
	if ( !fs.existsSync(tailwindDir) ) {
		fs.mkdirSync(tailwindDir);
		console.log('SUCCESS! Created folder: wp-tailwind');
	} else {
		console.log('SKIPPED: wp-tailwind folder already exists!');
	}
	
	// 2. Write package.json
	if ( !fs.existsSync(packageJsonPath) ) {
		fs.writeFileSync(packageJsonPath, JSON.stringify(packageJsonContent, null, 2));
		console.log('SUCCESS! package.json written to wp-tailwind folder.');
	} else {
		console.log('SKIPPED: package.json already exists!');
	}
	
	// 3. Check if node_modules already exists
	const nodeModulesPath = path.join(tailwindDir, 'node_modules');
	if ( fs.existsSync(nodeModulesPath) ) {
		console.log('SKIPPED! node_modules already exists!');
		done();
		return;
	}
	
	// 4. Run `npm install` if node_modules doesn't exist
	exec('npm install', { cwd: tailwindDir }, (err, stdout, stderr) => {
		if (err) {
			console.error('ERROR: npm install failed:', stderr);
			done(err);
			return;
		}
		
		console.log('SUCCESS! npm install complete:\n', stdout);
		done();
	});
	
});


/**
 * Exclude PHPStorm Dirs from autosuggestions
 ***********************************************************************************************************************/
gulp.task('init-phpstorm-exclude-dirs', function (done) {
	const imlFile = './../../../.idea/'+ domain +'.iml';
	const excludePaths = [
		'wp-admin/css',
		'wp-includes/js',
		'wp-includes/css',
		'wp-includes/blocks',
		'wp-content/plugins/advanced-custom-fields-pro/assets/build',
		'wp-content/plugins/advanced-custom-fields-pro/assets/inc',
		'wp-content/plugins/disable-comments/assets',
		'wp-content/plugins/stops-core-theme-and-plugin-updates/css',
		'wp-content/plugins/cloudflare/stylesheets',
		'wp-content/plugins/debug-log-manager/assets',
		'wp-content/plugins/categories-images/assets',
		'wp-content/plugins/better-wp-security/dist',
		'wp-content/plugins/better-wp-security/core/admin-pages/css',
		'wp-content/plugins/better-wp-security/vendor-prod/stellarwp/telemetry/src/resources',
		'wp-content/plugins/query-monitor/assets',
		'wp-content/plugins/redis-cache/assets',
		'wp-content/plugins/svg-support/css',
		'wp-content/plugins/svg-support/scss',
		'wp-content/plugins/windpress/build/assets',
	];
	
	let content = fs.readFileSync(imlFile, 'utf8');
	excludePaths.forEach(p => {
		const excludeTag = `<excludeFolder url="file://$MODULE_DIR$/${p}" />`;
		if (!content.includes(excludeTag)) {
			content = content.replace(/(<content[^>]*>)/, `$1\n    ${excludeTag}`);
		}
	});
	fs.writeFileSync(imlFile, content);
	console.log('SUCCESS! Excluded directories were updated.');
	done();
});


/**
 * Watch Task
 ***********************************************************************************************************************/
gulp.task('watch', function () {
	// Init BrowserSync
	browserSync.init(browserSyncOptions, (err, bs) => {
		if ( err ) {
			console.error('BrowserSync error:', err.message);
		}
	});
	
	// Run Tailwind Watch (Only Once)
	spawn('npx', ['@tailwindcss/cli', '-i', basePaths.css.input, '-o', basePaths.css.output, '--optimize', '--watch'], {
		stdio: 'inherit',
		shell: true
	}).on('error', (err) => {
		console.error('Tailwind Error:', err.message);
	});
	
	// Reload on PHP changes
	gulp.watch(basePaths.php).on('change', browserSync.reload);
	
	// Watch for Tailwind CSS changes & reload after recompilation
	gulp.watch(basePaths.css.output).on('change', browserSync.reload);
});


/**
 * Build Task [--minify] or [--optimize]
 ***********************************************************************************************************************/
gulp.task('build', function (done) {
	
	spawn('npx', ['@tailwindcss/cli', '-i', basePaths.css.input, '-o', basePaths.css.output, '--minify'], {
		stdio: 'inherit',
		shell: true
	}).on('error', (err) => {
		console.error('Tailwind Error:', err.message);
	}).on('close', done);
	
});


/**
 * Init Task
 ***********************************************************************************************************************/
gulp.task('default', gulp.series(
	'init-wp-tailwind',
	'init-phpstorm-exclude-dirs',
	'init-prettier',
	'build',
	function finalLog(done) {
		console.log('✅  Initial Setup is completed successfully!');
		done();
	}
));