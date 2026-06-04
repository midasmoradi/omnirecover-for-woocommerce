/**
 * External dependencies
 */
import {
	Button,
	Panel,
	PanelBody,
	SelectControl,
	TextareaControl,
	TextControl,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { render, createElement, Fragment, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import './style.css';

apiFetch.use( apiFetch.createRootURLMiddleware( omnirecoverAdmin.restRoot ) );
apiFetch.use( apiFetch.createNonceMiddleware( omnirecoverAdmin.nonce ) );

function App() {
	const [ settings, setSettings ] = useState( {} );
	const [ analytics, setAnalytics ] = useState( {} );
	const [ saving, setSaving ] = useState( false );
	const [ aiPrompt, setAiPrompt ] = useState( '' );
	const [ aiOut, setAiOut ] = useState( '' );
	const isPro = omnirecoverAdmin.capabilities && omnirecoverAdmin.capabilities.isPro;

	useEffect( () => {
		apiFetch( { path: 'omnirecover/v1/settings' } ).then( setSettings );
		apiFetch( { path: 'omnirecover/v1/analytics' } ).then( setAnalytics );
	}, [] );

	const save = () => {
		setSaving( true );
		apiFetch( {
			path: 'omnirecover/v1/settings',
			method: 'POST',
			data: settings,
		} )
			.then( setSettings )
			.finally( () => setSaving( false ) );
	};

	const runAi = () => {
		setAiOut( '' );
		apiFetch( {
			path: 'omnirecover/v1/ai/message',
			method: 'POST',
			data: { prompt: aiPrompt },
		} )
			.then( ( res ) => setAiOut( res.text || '' ) )
			.catch( ( e ) => setAiOut( e.message || String( e ) ) );
	};

	return createElement(
		Fragment,
		null,
		createElement( 'h1', null, __( 'OmniRecover', 'omnirecover-for-woocommerce' ) ),
		createElement(
			'p',
			null,
			__( 'Carts recovered:', 'omnirecover-for-woocommerce' ),
			' ',
			analytics.carts_recovered ?? 0
		),
		createElement(
			'p',
			null,
			__( 'Messages sent (rows):', 'omnirecover-for-woocommerce' ),
			' ',
			analytics.messages_sent ?? 0
		),
		createElement(
			'p',
			null,
			__( 'Revenue (unique recovered orders):', 'omnirecover-for-woocommerce' ),
			' ',
			analytics.revenue_total ?? 0
		),
		isPro && analytics.by_channel
			? createElement(
					'pre',
					null,
					JSON.stringify( analytics.by_channel, null, 2 )
			  )
			: null,
		createElement(
			Panel,
			null,
			createElement(
				PanelBody,
				{
					title: __( 'Channel and timing', 'omnirecover-for-woocommerce' ),
					initialOpen: true,
				},
				createElement( SelectControl, {
					label: __( 'Active channel', 'omnirecover-for-woocommerce' ),
					value: settings.active_channel || 'email',
					options: [
						{ label: __( 'Email', 'omnirecover-for-woocommerce' ), value: 'email' },
						{ label: __( 'WhatsApp', 'omnirecover-for-woocommerce' ), value: 'whatsapp' },
						{ label: __( 'Telegram', 'omnirecover-for-woocommerce' ), value: 'telegram' },
						{ label: __( 'SMS (Twilio)', 'omnirecover-for-woocommerce' ), value: 'sms' },
					],
					onChange: ( v ) =>
						setSettings( { ...settings, active_channel: v } ),
				} ),
				createElement( TextControl, {
					label: __( 'Abandon delay (minutes)', 'omnirecover-for-woocommerce' ),
					type: 'number',
					value: String( settings.abandon_delay_minutes ?? 120 ),
					onChange: ( v ) =>
						setSettings( {
							...settings,
							abandon_delay_minutes: parseInt( v, 10 ) || 120,
						} ),
				} ),
				isPro
					? createElement( TextareaControl, {
							label: __(
								'Fallback chain (one channel slug per line: email, whatsapp, telegram, sms)',
								'omnirecover-for-woocommerce'
							),
							help: __(
								'Order is tried after the active channel.',
								'omnirecover-for-woocommerce'
							),
							value: ( settings.fallback_chain || [] ).join( '\n' ),
							onChange: ( v ) =>
								setSettings( {
									...settings,
									fallback_chain: v
										.split( /\n+/ )
										.map( ( s ) => s.trim() )
										.filter( Boolean ),
								} ),
					  } )
					: null
			),
			createElement(
				PanelBody,
				{ title: __( 'Templates', 'omnirecover-for-woocommerce' ) },
				createElement( TextControl, {
					label: __( 'Email subject', 'omnirecover-for-woocommerce' ),
					value: settings.email_subject || '',
					onChange: ( v ) =>
						setSettings( { ...settings, email_subject: v } ),
				} ),
				createElement( TextareaControl, {
					label: __( 'Email body', 'omnirecover-for-woocommerce' ),
					value: settings.email_body || '',
					onChange: ( v ) =>
						setSettings( { ...settings, email_body: v } ),
				} ),
				createElement( TextareaControl, {
					label: __( 'WhatsApp body', 'omnirecover-for-woocommerce' ),
					value: settings.whatsapp_body || '',
					onChange: ( v ) =>
						setSettings( { ...settings, whatsapp_body: v } ),
				} ),
				createElement( TextareaControl, {
					label: __( 'Telegram body', 'omnirecover-for-woocommerce' ),
					value: settings.telegram_body || '',
					onChange: ( v ) =>
						setSettings( { ...settings, telegram_body: v } ),
				} ),
				createElement( TextareaControl, {
					label: __( 'SMS body', 'omnirecover-for-woocommerce' ),
					value: settings.sms_body || '',
					onChange: ( v ) =>
						setSettings( { ...settings, sms_body: v } ),
				} )
			),
			createElement(
				PanelBody,
				{ title: __( 'Gateways', 'omnirecover-for-woocommerce' ) },
				createElement( TextControl, {
					label: __( 'UltraMsg instance', 'omnirecover-for-woocommerce' ),
					value: settings.ultramsg_instance || '',
					onChange: ( v ) =>
						setSettings( { ...settings, ultramsg_instance: v } ),
				} ),
				createElement( TextControl, {
					label: __( 'UltraMsg token', 'omnirecover-for-woocommerce' ),
					value: settings.ultramsg_token || '',
					onChange: ( v ) =>
						setSettings( { ...settings, ultramsg_token: v } ),
				} ),
				createElement( TextControl, {
					label: __( 'Telegram bot token', 'omnirecover-for-woocommerce' ),
					value: settings.telegram_bot_token || '',
					onChange: ( v ) =>
						setSettings( { ...settings, telegram_bot_token: v } ),
				} ),
				createElement( TextControl, {
					label: __( 'Twilio SID', 'omnirecover-for-woocommerce' ),
					value: settings.twilio_sid || '',
					onChange: ( v ) =>
						setSettings( { ...settings, twilio_sid: v } ),
				} ),
				createElement( TextControl, {
					label: __( 'Twilio token', 'omnirecover-for-woocommerce' ),
					value: settings.twilio_token || '',
					onChange: ( v ) =>
						setSettings( { ...settings, twilio_token: v } ),
				} ),
				createElement( TextControl, {
					label: __( 'Twilio From', 'omnirecover-for-woocommerce' ),
					value: settings.twilio_from || '',
					onChange: ( v ) =>
						setSettings( { ...settings, twilio_from: v } ),
				} ),
				isPro
					? createElement( TextControl, {
							label: __( 'OpenAI API key', 'omnirecover-for-woocommerce' ),
							value: settings.openai_api_key || '',
							onChange: ( v ) =>
								setSettings( { ...settings, openai_api_key: v } ),
					  } )
					: null
			),
			isPro
				? createElement(
						PanelBody,
						{ title: __( 'AI message (Pro)', 'omnirecover-for-woocommerce' ) },
						createElement( TextareaControl, {
							label: __( 'Prompt', 'omnirecover-for-woocommerce' ),
							value: aiPrompt,
							onChange: setAiPrompt,
						} ),
						createElement( Button, { variant: 'secondary', onClick: runAi }, __( 'Generate', 'omnirecover-for-woocommerce' ) ),
						createElement( TextareaControl, {
							label: __( 'Output', 'omnirecover-for-woocommerce' ),
							value: aiOut,
							onChange: setAiOut,
						} )
				  )
				: null
		),
		createElement(
			Button,
			{ variant: 'primary', onClick: save, isBusy: saving },
			__( 'Save settings', 'omnirecover-for-woocommerce' )
		)
	);
}

document.addEventListener( 'DOMContentLoaded', () => {
	const el = document.getElementById( 'omnirecover-root' );
	if ( el ) {
		render( createElement( App ), el );
	}
} );
