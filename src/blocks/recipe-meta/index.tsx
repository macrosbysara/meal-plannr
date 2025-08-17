import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { receipt } from '@wordpress/icons';
import metadata from './block.json';
import Edit from './edit';

registerBlockType( metadata.name, {
	icon: receipt,
	edit: Edit,
	save: ( { attributes } ) => {
		const { protein, carbs, fat, calories } = attributes;
		return (
			<div { ...useBlockProps.save() }>
				<ul>
					<li>Carbs: { carbs }g</li>
					<li>Fat: { fat }g</li>
					<li>Protein: { protein }g</li>
					<li>Calories: { calories }</li>
				</ul>
			</div>
		);
	},
} );
