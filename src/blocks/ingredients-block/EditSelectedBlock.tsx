import {
	TextControl,
	SelectControl,
	TextareaControl,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
export default function EditSelectedBlock( { attributes, setAttributes } ) {
	const {
		name,
		quantityVolume,
		unitVolume,
		quantityWeight,
		unitWeight,
		description,
	} = attributes;
	const [ weight, setWeight ] = useState( quantityWeight );
	const [ volume, setVolume ] = useState( quantityVolume );

	useEffect( () => {
		if ( '' !== volume && ! isNaN( Number( volume ) ) ) {
			setAttributes( { quantityWeight: Number( weight ) } );
		} else {
			setAttributes( { quantityWeight: 0 } );
		}
	}, [ weight ] );

	useEffect( () => {
		if ( '' !== volume && ! isNaN( Number( volume ) ) ) {
			setAttributes( { quantityVolume: Number( volume ) } );
		} else {
			setAttributes( { quantityVolume: 0 } );
		}
	}, [ volume ] );

	return (
		<>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ 'Ingredient Name' }
				value={ name }
				placeholder="Enter ingredient name"
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
					placeholder="2"
					value={ volume }
					onChange={ ( value ) => setVolume( value ) }
				/>
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ 'Unit (Volume)' }
					value={ unitVolume }
					options={ [
						{ label: 'Select unit', value: '' },
						{ label: 'Cup', value: 'cup' },
						{ label: 'Tbsp', value: 'tbsp' },
						{ label: 'Tsp', value: 'tsp' },
						{ label: 'ml', value: 'ml' },
						{ label: 'fl. oz.', value: 'fl. oz.' },
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
					placeholder="2"
					label={ 'Qty (Weight)' }
					value={ weight }
					onChange={ ( value ) => {
						setWeight( value );
					} }
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
