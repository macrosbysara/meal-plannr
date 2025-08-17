import { useBlockProps } from '@wordpress/block-editor';
import { TextControl } from '@wordpress/components';
import { useMacrosSync } from '../../hooks/useMacrosSync';
import { useState, useEffect } from '@wordpress/element';

export default function Edit( { attributes, setAttributes, isSelected } ) {
	useMacrosSync();
	const { protein, carbs, fat, calories } = attributes;
	const blockProps = useBlockProps();

	// Local state for macro fields as strings to allow decimals
	const [ macroValues, setMacroValues ] = useState( {
		carbs: carbs?.toString() ?? '',
		fat: fat?.toString() ?? '',
		protein: protein?.toString() ?? '',
		calories: calories?.toString() ?? '',
	} );

	useEffect( () => {
		setAttributes( {
			carbs: parseFloat( macroValues.carbs ) || 0,
			fat: parseFloat( macroValues.fat ) || 0,
			protein: parseFloat( macroValues.protein ) || 0,
			calories: parseFloat( macroValues.calories ) || 0,
		} );
	}, [ macroValues ] );

	// Update local state on input change
	const handleChange = ( key: string, val: string ) => {
		setMacroValues( ( prev ) => ( {
			...prev,
			[ key ]: val,
		} ) );
	};

	const macroFields = [
		{ key: 'carbs', label: 'Carbs', value: macroValues.carbs },
		{ key: 'fat', label: 'Fat', value: macroValues.fat },
		{ key: 'protein', label: 'Protein', value: macroValues.protein },
		{ key: 'calories', label: 'Calories', value: macroValues.calories },
	];

	return (
		<div { ...blockProps }>
			<h3>Macros</h3>
			<div
				style={ {
					display: 'inline-flex',
					gap: '8px',
					alignItems: 'stretch',
					justifyContent: 'space-between',
				} }
			>
				{ macroFields.map( ( { key, label, value } ) =>
					isSelected ? (
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							key={ key }
							label={
								'calories' === key
									? 'Calories'
									: `${ label } (g)`
							}
							value={ value }
							onChange={ ( val ) => handleChange( key, val ) }
						/>
					) : (
						<p>
							{ label }:{ ' ' }
							{ 'calories' !== key ? `${ value }g` : value }
						</p>
					)
				) }
			</div>
		</div>
	);
}
