import {
	TextControl,
	SelectControl,
	TextareaControl,
} from '@wordpress/components';
export default function EditSelectedBlock( { attributes, setAttributes } ) {
	const {
		name,
		quantityVolume,
		unitVolume,
		quantityWeight,
		unitWeight,
		description,
	} = attributes;
	return (
		<>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ 'Ingredient Name' }
				value={ name }
				onChange={ ( value ) => setAttributes( { name: value } ) }
			/>
			<TextareaControl
				__nextHasNoMarginBottom
				label={ 'Description' }
				value={ description }
				onChange={ ( value ) =>
					setAttributes( { description: value } )
				}
			/>

			<div style={ { display: 'flex', gap: '8px' } }>
				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ 'Qty (Volume)' }
					value={ quantityVolume }
					onChange={ ( value ) =>
						setAttributes( {
							quantityVolume: parseFloat( value ) || 0,
						} )
					}
				/>
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ 'Unit (Volume)' }
					value={ unitVolume }
					options={ [
						{ label: 'Select unit', value: '' },
						{ label: 'cup', value: 'cup' },
						{ label: 'tbsp', value: 'tbsp' },
						{ label: 'tsp', value: 'tsp' },
						{ label: 'ml', value: 'ml' },
					] }
					onChange={ ( value ) =>
						setAttributes( { unitVolume: value } )
					}
				/>
			</div>

			<div style={ { display: 'flex', gap: '8px' } }>
				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ 'Qty (Weight)' }
					value={ quantityWeight }
					onChange={ ( value ) =>
						setAttributes( {
							quantityWeight: parseFloat( value ) || 0,
						} )
					}
				/>
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ 'Unit (Weight)' }
					value={ unitWeight }
					options={ [
						{ label: 'Select unit', value: '' },
						{ label: 'g', value: 'g' },
						{ label: 'kg', value: 'kg' },
						{ label: 'oz', value: 'oz' },
						{ label: 'lb', value: 'lb' },
					] }
					onChange={ ( value ) =>
						setAttributes( { unitWeight: value } )
					}
				/>
			</div>
		</>
	);
}
