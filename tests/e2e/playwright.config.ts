/**
 * Playwright config for the E2E suite.
 *
 * Standalone (the project builds with Grunt, not @wordpress/scripts)
 * and runs against the test env (.wp-env.test.json, port 8889).
 */

/**
 * External dependencies
 */
import path from 'path';
import { defineConfig, devices } from '@playwright/test';

process.env.WP_ARTIFACTS_PATH ??= path.join( process.cwd(), 'artifacts' );
process.env.STORAGE_STATE_PATH ??= path.join(
	process.env.WP_ARTIFACTS_PATH,
	'storage-states/admin.json'
);
const baseURL = process.env.WP_BASE_URL || 'http://localhost:8889';

export default defineConfig( {
	testDir: './specs',
	outputDir: path.join( process.env.WP_ARTIFACTS_PATH, 'test-results' ),
	globalSetup: './config/global-setup.ts',
	reporter: process.env.CI ? [ [ 'github' ] ] : [ [ 'list' ] ],
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 2 : 0,
	// Specs share one WordPress site, so they can't run in parallel.
	workers: 1,
	timeout: 100_000,
	reportSlowTests: null,
	use: {
		baseURL,
		headless: true,
		locale: 'en-US',
		contextOptions: { reducedMotion: 'reduce', strictSelectors: true },
		storageState: process.env.STORAGE_STATE_PATH,
		actionTimeout: 10_000,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'on-first-retry',
	},
	webServer: {
		command: 'npm run wp-env:test start',
		url: baseURL,
		timeout: 120_000,
		reuseExistingServer: true,
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
} );
