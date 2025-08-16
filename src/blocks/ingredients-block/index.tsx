import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import metadata from './block.json';
import Edit from './edit';

registerBlockType( metadata.name, {
	edit: Edit,
	save: ( { attributes } ) => {
		const {
			name,
			quantityVolume,
			unitVolume,
			quantityWeight,
			unitWeight,
			description,
		} = attributes;
		const blockProps = useBlockProps.save();
		const hasWeight = 0 !== quantityWeight;
		const hasVolume = 0 !== quantityVolume;
		return (
			<li { ...blockProps }>
				<div
					style={ {
						display: 'flex',
						gap: '.5rem',
						flexWrap: 'wrap',
						alignItems: 'center',
					} }
				>
					<span className="ingredient-name">{ name }</span>
					{ description && (
						<span className="ingredient-notes">
							{ description }
						</span>
					) }
					{ hasVolume && (
						<span>
							{ quantityVolume } { unitVolume }
						</span>
					) }
					{ hasWeight &&
						( hasVolume ? (
							<span>
								({ quantityWeight } { unitWeight })
							</span>
						) : (
							<span>
								{ quantityWeight } { unitWeight }
							</span>
						) ) }
				</div>
			</li>
		);
	},
} );
