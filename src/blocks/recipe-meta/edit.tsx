import { useBlockProps } from '@wordpress/block-editor';
import { TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes, isSelected } ) {
	const { protein, carbs, fat, calories } = attributes;
	const blockProps = useBlockProps();

	const macroFields = [
		{ key: 'protein', label: 'Protein', value: protein },
		{ key: 'carbs', label: 'Carbs', value: carbs },
		{ key: 'fat', label: 'Fat', value: fat },
		{ key: 'calories', label: 'Calories', value: calories },
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
							onChange={ ( val ) =>
								setAttributes( {
									[ key ]: parseFloat( val ) || 0,
								} )
							}
						/>
					) : (
						<p>
							{ label }:{ ' ' }
							{ 'calories' !== key ? `${ value } (g)` : value }
						</p>
					)
				) }
			</div>
		</div>
	);
}
