import { useBlockProps } from '@wordpress/block-editor';
import {
	TextControl,
	__experimentalNumberControl as NumberControl,
	SelectControl,
	TextareaControl,
} from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const {
		name,
		quantityVolume,
		unitVolume,
		quantityWeight,
		unitWeight,
		notes,
	} = attributes;
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ 'Ingredient Name' }
				value={ name }
				onChange={ ( value ) => setAttributes( { name: value } ) }
			/>

			<div style={ { display: 'flex', gap: '8px' } }>
				<NumberControl
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
				<NumberControl
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

			<TextareaControl
				__nextHasNoMarginBottom
				label={ 'Description' }
				value={ notes }
				onChange={ ( value ) => setAttributes( { notes: value } ) }
			/>
		</div>
	);
}
